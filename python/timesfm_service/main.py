"""
FastAPI service exposing Chronos-2 forecasts to the EBS PHP backend.

Run:  ./run.sh   (or)  uvicorn main:app --host 127.0.0.1 --port 8081
"""
import time
import logging
from datetime import timedelta
from typing import List

from fastapi import FastAPI, HTTPException

from config import config
from models import (
    ForecastRequest, ForecastResponse, ForecastResult, ForecastPoint,
    HealthResponse, InfoResponse, ForecastItem,
)
from chronos_client import ChronosClient as InferenceClient

logging.basicConfig(
    level=config.LOG_LEVEL.upper(),
    format="%(asctime)s [%(levelname)s] %(name)s — %(message)s",
)
log = logging.getLogger("timesfm-service")


app = FastAPI(
    title="EBS Time-Series Forecasting Service",
    version="2.0.0",
    description="Local Chronos-2 (Amazon) for time-series electricity load forecasting.",
)


# Lazy-init the client (one per worker)
_client: InferenceClient | None = None
def _get_client() -> InferenceClient:
    global _client
    if _client is None:
        _client = InferenceClient()
    return _client


# ── Helpers ──────────────────────────────────────────────────────────────────

_FREQ_DELTA = {
    "15min": timedelta(minutes=15),
    "H":     timedelta(hours=1),
    "D":     timedelta(days=1),
}


def _build_forecast_points(start_dt, freq: str, p50: List[float],
                           p10: List[float] | None, p90: List[float] | None
                           ) -> List[ForecastPoint]:
    """Turn raw forecast arrays into ForecastPoint objects with explicit datetimes."""
    delta = _FREQ_DELTA[freq]
    out: List[ForecastPoint] = []
    for i, v in enumerate(p50):
        out.append(ForecastPoint(
            datetime=start_dt + delta * i,
            p50=float(v),
            p10=float(p10[i]) if p10 is not None and i < len(p10) else None,
            p90=float(p90[i]) if p90 is not None and i < len(p90) else None,
        ))
    return out


def _next_dt_after_history(item: ForecastItem, freq: str):
    """Forecast starts one step after the last history timestamp."""
    last = item.history[-1].datetime
    return last + _FREQ_DELTA[freq]


# ── Routes ───────────────────────────────────────────────────────────────────

@app.get("/health", response_model=HealthResponse)
def health() -> HealthResponse:
    reachable = False
    try:
        reachable = _get_client().health_check()
    except Exception as e:  # model load failed etc.
        log.warning("health check failed: %s", e)
    return HealthResponse(
        status="ok" if reachable else "degraded",
        vertex_ai_reachable=reachable,    # field kept as-is for backwards compat with V4Algorithm
    )


@app.get("/info", response_model=InfoResponse)
def info() -> InfoResponse:
    return InfoResponse(
        model=_get_client().model_label,
        project="local",
        location="local",
        max_context=config.MAX_CONTEXT,
        max_batch_size=config.MAX_BATCH_SIZE,
        frequencies_supported=list(_FREQ_DELTA.keys()),
    )


@app.post("/forecast", response_model=ForecastResponse)
def forecast(req: ForecastRequest) -> ForecastResponse:
    t0 = time.monotonic()
    client = _get_client()

    if len(req.items) > config.MAX_BATCH_SIZE:
        raise HTTPException(status_code=400,
            detail=f"Max {config.MAX_BATCH_SIZE} items per request; got {len(req.items)}")

    horizons = {item.horizon_steps for item in req.items}
    if len(horizons) > 1:
        raise HTTPException(status_code=400,
            detail="All items in one request must share the same horizon_steps. "
                   "Split into separate requests if you need mixed horizons.")
    horizon = next(iter(horizons))

    # Route: if ANY item carries covariates on history or has a future_covariates list,
    # use the multivariate path (predict_df). Otherwise stay on the univariate fast path.
    has_cov = any(
        (item.future_covariates is not None and len(item.future_covariates) > 0) or
        any((h.temp is not None or h.feelslike is not None) for h in item.history)
        for item in req.items
    )

    try:
        if has_cov:
            items_payload = []
            for item in req.items:
                hist = [{"datetime": h.datetime, "value": h.value,
                          "temp": h.temp, "feelslike": h.feelslike} for h in item.history]
                fut = [{"datetime": f.datetime, "temp": f.temp, "feelslike": f.feelslike}
                       for f in (item.future_covariates or [])]
                items_payload.append({"pod_id": item.pod_id, "history": hist, "future": fut})
            forecasts = client.predict_with_covariates(items_payload, horizon=horizon)
        else:
            contexts = [[h.value for h in item.history] for item in req.items]
            forecasts = client.predict_batch(contexts, horizon=horizon, frequency=req.frequency)
    except Exception as e:
        log.exception("Chronos call failed")
        raise HTTPException(status_code=502, detail=f"Chronos inference error: {e}") from e

    results: List[ForecastResult] = []
    for item, fc in zip(req.items, forecasts):
        start_dt = _next_dt_after_history(item, req.frequency)
        points = _build_forecast_points(start_dt, req.frequency, fc.p50, fc.p10, fc.p90)
        results.append(ForecastResult(pod_id=item.pod_id, forecast=points))

    elapsed_ms = int((time.monotonic() - t0) * 1000)
    log.info("forecast served — items=%d horizon=%d freq=%s elapsed=%dms",
             len(req.items), horizon, req.frequency, elapsed_ms)

    return ForecastResponse(
        results=results,
        model=client.model_label,
        elapsed_ms=elapsed_ms,
    )
