# Chronos-2 Forecasting Microservice

FastAPI microservice that wraps Amazon's **Chronos-2** time-series foundation model
(`amazon/chronos-2` on HuggingFace) and exposes a clean HTTP API for the EBS PHP
backend to call.

> **History**: this folder originally hosted a TimesFM-on-Vertex-AI wrapper. We
> pivoted to self-hosted Chronos-2 on 2026-05-29 because Vertex's Model Garden
> default container caps horizon at 128 steps (unusable for monthly D+1 forecasts
> at 15-minute granularity) and costs ~$1.50/hour even when idle. The folder name
> is kept for historical continuity; the code talks to Chronos only.

## Architecture

```
PHP ForecastModel.php  ───HTTP──>  FastAPI (this service)  ──in-process──>  Chronos-2
        (v4 phase)                  localhost:8081                            CPU
```

The PHP side has v1/v2/v3 as SQL-based algorithm phases in `estimate()`. v4 (this
service) follows the same pattern but instead of building SQL it POSTs POD history
to this microservice and persists the returned forecasts as `algorithm='v4'`.

## Why Chronos-2 (Oct 2025)

- **Open-weight** on HuggingFace (`amazon/chronos-2`, ~200MB). No cloud account, no
  recurring cost, no data leaving the network.
- **Encoder-only forecast head** — predicts up to 1024+ steps in a single forward
  pass without autoregressive error compounding (TimesFM-2.0 caps at 128 on Vertex).
- **CPU-friendly** — 500M parameters, runs in ~3s/POD for a daily 96-step forecast
  on a Ryzen-class CPU. Server has 50 cores → plenty of headroom.
- **Multivariate native** — accepts exogenous covariates (we pass `temp` + `feelslike`).
- **Outperforms TimesFM-2.5 and Moirai-2** on recent 2026 benchmarks (TSFM.ai,
  Decathlon, arXiv:2602.10848).

See `TD-B1_ablation_implementation.md` (in `/var/www/ebsv2/todo/`) for the full pivot
story and validation numbers (WAPE 12% on Tribunal Tulcea cust 592, beating v1 22%
and v2/v3 27-28%).

## Setup

```bash
cd /var/www/ebsv2/python/timesfm_service

# venv (use virtualenv — python3-venv apt package is not installed)
virtualenv -p python3 venv
source venv/bin/activate
pip install -r requirements.txt

cp .env.example .env       # defaults are usually fine
```

First request triggers the model download (~200MB into `$HF_HOME`).

## Run

### Development
```bash
source venv/bin/activate
./run.sh   # or: uvicorn main:app --host 127.0.0.1 --port 8081
```

### Production (systemd)
```bash
sudo cp deploy/systemd.service /etc/systemd/system/ebs-chronos.service
sudo systemctl daemon-reload
sudo systemctl enable --now ebs-chronos
sudo systemctl status  ebs-chronos
```

The unit runs as `www-data` and reads `.env` from this folder.

### Optional: Apache proxy for browser access
PHP talks to `127.0.0.1:8081` directly, so this is only needed if you want
to hit the API from a browser or another LAN host.
```bash
sudo bash deploy/setup-apache.sh
# → /timesfm/health, /timesfm/forecast, etc. become accessible on :8080
```

## API

### `POST /forecast`
```json
{
  "frequency": "15min",
  "items": [
    {
      "pod_id": "RO002E240654735",
      "history": [
        {"datetime": "2026-03-11T00:00:00", "value": 0.0205, "temp": 4.2, "feelslike": 1.1},
        …
      ],
      "horizon_steps": 96,
      "future_covariates": [
        {"datetime": "2026-04-01T00:00:00", "temp": 12.0, "feelslike": 10.5},
        …
      ]
    }
  ]
}
```

- `temp` / `feelslike` are optional. If present on EITHER history points or as
  `future_covariates`, the service routes to the multivariate `predict_df` path;
  otherwise it uses the faster univariate `predict_quantiles` path.
- All items in one request must share the same `horizon_steps`.

Response:
```json
{
  "results": [
    {
      "pod_id": "RO002E240654735",
      "forecast": [
        {"datetime": "2026-04-01T00:00:00", "p10": 0.0018, "p50": 0.0021, "p90": 0.0024},
        …
      ]
    }
  ],
  "model": "chronos-2@local",
  "elapsed_ms": 3320
}
```

### `GET /health`
Returns `200 {"status": "ok", "vertex_ai_reachable": true}` — the `vertex_ai_reachable`
field is a legacy name kept so `V4Algorithm.php::v4ServiceReady()` keeps working;
it really means "model loaded and a probe forecast succeeded."

### `GET /info`
Model + tuning metadata.

## Production notes

- systemd unit auto-restarts on failure (`Restart=on-failure`, 5s backoff).
- HF cache lives at `/var/www/.cache/huggingface` and must be writable by `www-data`
  (handled by `HF_HOME` in `.env` + the systemd unit's `ReadWritePaths`).
- Model is loaded lazily on the first `/forecast` call (~1s on warm cache).
- Falls back gracefully in PHP: if this service is down, `V4Algorithm.php::v4ServiceReady()`
  returns false and the v4 phase is skipped cleanly — v1/v2/v3 continue normally.
