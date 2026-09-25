"""
Local Chronos-2 pipeline.

Self-hosted: no Vertex AI, no recurring cost, no external network calls after first
model download (~200MB to HF cache). See TD-B1 §"Pivot: TimesFM → Chronos-2" for
the full rationale.

Two prediction paths:
- Univariate / consumption-only — uses `predict_quantiles` on stacked tensors. Fast.
- Multivariate / with covariates — uses `predict_df` with pandas frames. Slightly
  slower but supports `temp` + `feelslike` as future-known regressors.

Either way the result shape (point + p10/p50/p90 list per series) is the same so
main.py / V4Algorithm.php don't care which path was taken.

Model:  amazon/chronos-2  (loaded lazily on first call)
"""
from __future__ import annotations
from dataclasses import dataclass
from typing import List, Optional, Sequence, Mapping, Any
import logging
import threading
import time

import numpy as np
import pandas as pd
import torch
from chronos import BaseChronosPipeline

from config import config

log = logging.getLogger(__name__)

CHRONOS_MODEL_ID = "amazon/chronos-2"

# Covariates the v4 pipeline knows how to forward. Anything else on a HistoryPoint
# / FutureCovariate is ignored — keeps the surface honest and stable.
COVARIATE_COLUMNS = ("temp", "feelslike")


@dataclass
class ChronosForecast:
    point: List[float]
    p10:   Optional[List[float]] = None
    p50:   Optional[List[float]] = None
    p90:   Optional[List[float]] = None


class ChronosClient:
    _instance_lock = threading.Lock()

    def __init__(self) -> None:
        self._lock = threading.Lock()
        self._pipe: Optional[BaseChronosPipeline] = None
        self._model_label = "chronos-2@local"
        log.info("Chronos client constructed — model will load on first predict()")

    def _ensure_loaded(self) -> None:
        if self._pipe is not None:
            return
        with self._instance_lock:
            if self._pipe is not None:
                return
            log.info("Loading Chronos-2 pipeline (CPU)…")
            t0 = time.time()
            self._pipe = BaseChronosPipeline.from_pretrained(
                CHRONOS_MODEL_ID,
                device_map="cpu",
                dtype=torch.float32,
            )
            log.info("Chronos-2 loaded in %.1fs (ctx=%d, pred=%d)",
                     time.time() - t0, self._pipe.model_context_length, self._pipe.model_prediction_length)

    @property
    def model_label(self) -> str:
        return self._model_label

    # ── univariate fast path ─────────────────────────────────────────────────
    def predict_batch(
        self,
        contexts: List[List[float]],
        horizon: int,
        frequency: str = "H",   # kept for API parity; Chronos is frequency-agnostic
    ) -> List[ChronosForecast]:
        """Consumption-only — no covariates. Uses stacked tensors + predict_quantiles."""
        if not contexts:
            return []
        self._ensure_loaded()

        max_ctx = self._pipe.model_context_length
        trimmed = [c[-max_ctx:] for c in contexts]
        target_len = max(len(c) for c in trimmed)
        # Left-pad shorter contexts with their first value so the predict head sees real
        # data at the tail and stacking works.
        padded = [[c[0]] * (target_len - len(c)) + list(c) if len(c) < target_len else list(c)
                  for c in trimmed]

        x = torch.tensor(padded, dtype=torch.float32).unsqueeze(1)
        log.debug("Chronos univariate predict — batch=%d ctx=%d horizon=%d",
                  len(contexts), target_len, horizon)
        with self._lock:
            t0 = time.time()
            qpred, _ = self._pipe.predict_quantiles(
                inputs=x,
                prediction_length=horizon,
                quantile_levels=[0.1, 0.5, 0.9],
                limit_prediction_length=False,
            )
            elapsed = time.time() - t0
        log.info("Chronos uni batch=%d horizon=%d → %.2fs (%.2fs/series)",
                 len(contexts), horizon, elapsed, elapsed / len(contexts))

        results: List[ChronosForecast] = []
        for i in range(len(contexts)):
            qs = qpred[i].squeeze(0).cpu().numpy()
            results.append(ChronosForecast(
                point=[float(v) for v in qs[:, 1]],
                p10=[float(v) for v in qs[:, 0]],
                p50=[float(v) for v in qs[:, 1]],
                p90=[float(v) for v in qs[:, 2]],
            ))
        return results

    # ── multivariate path with future-known covariates ───────────────────────
    def predict_with_covariates(
        self,
        items: Sequence[Mapping[str, Any]],
        horizon: int,
    ) -> List[ChronosForecast]:
        """
        Each item is a dict shaped like:
          { 'pod_id': str,
            'history': [ {datetime, value, temp?, feelslike?}, ... ],
            'future':  [ {datetime, temp, feelslike}, ... ]    # len == horizon
          }
        Returns one ChronosForecast per item, in the same order.
        """
        if not items:
            return []
        self._ensure_loaded()

        hist_rows, fut_rows = [], []
        ids_order = []
        for it in items:
            pod = it["pod_id"]
            ids_order.append(pod)
            for hp in it["history"]:
                row = {"item_id": pod,
                       "timestamp": pd.to_datetime(hp["datetime"]),
                       "target": float(hp["value"])}
                for c in COVARIATE_COLUMNS:
                    v = hp.get(c)
                    if v is not None:
                        row[c] = float(v)
                hist_rows.append(row)
            for fp in it.get("future", []):
                row = {"item_id": pod,
                       "timestamp": pd.to_datetime(fp["datetime"])}
                for c in COVARIATE_COLUMNS:
                    v = fp.get(c)
                    if v is not None:
                        row[c] = float(v)
                fut_rows.append(row)

        hist_df = pd.DataFrame(hist_rows)
        fut_df  = pd.DataFrame(fut_rows) if fut_rows else None

        log.debug("Chronos cov predict — batch=%d horizon=%d cov cols=%s",
                  len(items), horizon,
                  [c for c in COVARIATE_COLUMNS if c in hist_df.columns])

        with self._lock:
            t0 = time.time()
            out = self._pipe.predict_df(
                df=hist_df,
                future_df=fut_df,
                prediction_length=horizon,
                quantile_levels=[0.1, 0.5, 0.9],
            )
            elapsed = time.time() - t0
        log.info("Chronos cov batch=%d horizon=%d → %.2fs (%.2fs/series)",
                 len(items), horizon, elapsed, elapsed / max(len(items), 1))

        results: List[ChronosForecast] = []
        for pod in ids_order:
            sub = out[out["item_id"] == pod].sort_values("timestamp")
            p10 = sub["0.1"].tolist()
            p50 = sub["0.5"].tolist()
            p90 = sub["0.9"].tolist()
            results.append(ChronosForecast(
                point=[float(v) for v in p50],
                p10=[float(v) for v in p10],
                p50=[float(v) for v in p50],
                p90=[float(v) for v in p90],
            ))
        return results

    def predict_one(self, context: List[float], horizon: int, frequency: str = "H") -> ChronosForecast:
        return self.predict_batch([context], horizon, frequency)[0]

    def health_check(self) -> bool:
        try:
            ctx = [(i % 24) * 0.1 + 1.0 for i in range(96)]
            self.predict_one(ctx, horizon=4, frequency="H")
            return True
        except Exception as e:
            log.warning("Chronos health check failed: %s", e)
            return False
