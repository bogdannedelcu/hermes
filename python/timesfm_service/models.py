"""Pydantic request / response schemas for the forecast API."""
from datetime import datetime
from typing import List, Optional, Literal
from pydantic import BaseModel, Field


# ── Request ───────────────────────────────────────────────────────────────────

class HistoryPoint(BaseModel):
    datetime: datetime
    value: float
    # Optional covariates aligned with this timestamp — kept on the same struct so the
    # caller doesn't have to maintain a second array. Chronos-2 supports multivariate
    # input natively.
    temp:      Optional[float] = None
    feelslike: Optional[float] = None


class FutureCovariate(BaseModel):
    """Covariates known at forecast time (e.g. weather observations or forecasts)."""
    datetime: datetime
    temp:      Optional[float] = None
    feelslike: Optional[float] = None


class ForecastItem(BaseModel):
    pod_id: str = Field(..., description="POD identifier (used only as label for the response)")
    history: List[HistoryPoint] = Field(..., min_length=24,
        description="Historical readings ordered by datetime ascending. Min ~24 points; recommended >=168 (1 week).")
    horizon_steps: int = Field(..., gt=0, le=8760,
        description="Number of future steps to predict (e.g. 2880 = 30 days at 15-min).")
    future_covariates: Optional[List[FutureCovariate]] = Field(None,
        description="Optional future-known covariates aligned to the horizon timestamps. If "
                    "provided, the count must equal horizon_steps. Forwarded to Chronos-2 as "
                    "exogenous regressors.")


class ForecastRequest(BaseModel):
    frequency: Literal["H", "D", "15min"] = Field("H",
        description="Sampling frequency of `history` values: H=hourly, D=daily, 15min=15-minute.")
    items: List[ForecastItem] = Field(..., min_length=1, max_length=64)


# ── Response ──────────────────────────────────────────────────────────────────

class ForecastPoint(BaseModel):
    datetime: datetime
    p10: Optional[float] = None
    p50: float = Field(..., description="Median forecast — use this as the point estimate.")
    p90: Optional[float] = None


class ForecastResult(BaseModel):
    pod_id: str
    forecast: List[ForecastPoint]


class ForecastResponse(BaseModel):
    results: List[ForecastResult]
    model:   str
    elapsed_ms: int


# ── Health / info ─────────────────────────────────────────────────────────────

class HealthResponse(BaseModel):
    status: Literal["ok", "degraded"]
    vertex_ai_reachable: bool


class InfoResponse(BaseModel):
    model: str
    project: str
    location: str
    max_context: int
    max_batch_size: int
    frequencies_supported: List[str]
