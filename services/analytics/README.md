# CafeFlow Analytics Service

A standalone FastAPI service that computes sales, product, and customer analytics plus a
7-day revenue forecast for CafeFlow.

It is **stateless and read-only**. It has no database and no write access to CafeFlow.
Laravel stays the source of truth: it gathers the data, sends it here over HTTP with a
shared service key, and shows the results in the admin dashboard. The browser never
talks to this service directly.

```
Admin browser ──► Laravel (session auth, policies) ──HTTP + X-Service-Key──► FastAPI
                         ▲                                                      │
                         └───────────────────── JSON results ◄──────────────────┘
```

## Requirements

- Python 3.11+ (developed and tested on 3.15)
- Dependencies are pinned in `requirements.txt` (runtime) and `requirements-dev.txt` (tests)

## Install & run

```bash
cd services/analytics
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements-dev.txt
cp .env.example .env
# put a real secret in .env, and the SAME value in Laravel's ANALYTICS_SERVICE_KEY
python -c "import secrets; print(secrets.token_urlsafe(48))"

uvicorn app.main:app --host 127.0.0.1 --port 8001 --reload
```

Interactive docs are at <http://127.0.0.1:8001/docs> (turned off when `ANALYTICS_ENV=production`).

### Configuration

| Variable | Default | Purpose |
|---|---|---|
| `ANALYTICS_SERVICE_KEY` | *(empty)* | Shared secret. Analytics endpoints return **503** while this is empty or `change-me`. |
| `ANALYTICS_ENV` | `local` | `production` turns off `/docs`. |
| `ANALYTICS_LOG_LEVEL` | `INFO` | Python logging level. Every request is logged with its status and duration. |

## Tests

```bash
.venv/bin/python -m pytest -q
```

The tests cover health, authentication failures (missing, wrong, and unconfigured key), every
analytics endpoint, input validation (negative or malformed money, inverted date ranges,
unknown fields, duplicate dates, horizon bounds), and forecast behaviour on known series.

## API

Every response uses the same envelope as the Laravel API:

```json
{ "success": true, "message": "Sales analytics calculated", "data": { } }
```

Errors return `success: false`. Validation errors (HTTP 422) also include
`errors: [{ "field": "orders.0.total", "message": "..." }]`.

All `/api/v1/*` endpoints require the header `X-Service-Key: <secret>`. Money values are
non-negative decimals with at most 2 dp, sent as strings or numbers. Unknown fields are
rejected.

| Status | Meaning |
|---|---|
| 200 | Success |
| 401 | Missing or wrong `X-Service-Key` |
| 422 | Invalid payload, or too little history to forecast |
| 503 | Service key not configured on the server |
| 500 | Unexpected error (logged server-side; no details leaked) |

### `GET /health`

Public. Reports liveness only and never exposes secrets.

```json
{ "success": true, "message": "OK",
  "data": { "status": "ok", "service": "cafeflow-analytics", "version": "1.0.0",
            "uptime_seconds": 512.3, "auth_configured": true } }
```

### `POST /api/v1/analytics/sales`

Request:

```json
{
  "start_date": "2026-10-01",
  "end_date": "2026-10-03",
  "orders": [
    { "order_id": 1, "placed_at": "2026-10-01T09:00:00Z", "total": "10.00", "customer_id": 7 },
    { "order_id": 2, "placed_at": "2026-10-03T08:00:00Z", "total": "9.50" }
  ],
  "previous_orders": [
    { "order_id": 0, "placed_at": "2026-09-28T09:00:00Z", "total": "12.00" }
  ]
}
```

`previous_orders` holds the period of equal length just before this one, and is used for growth.
Orders outside `start_date..end_date` are ignored. Days with no orders appear with revenue `0`.

Response `data`:

```json
{
  "total_revenue": 19.5, "order_count": 2, "average_order_value": 9.75,
  "revenue_by_date": [ { "date": "2026-10-01", "revenue": 10.0, "orders": 1 },
                       { "date": "2026-10-02", "revenue": 0.0,  "orders": 0 },
                       { "date": "2026-10-03", "revenue": 9.5,  "orders": 1 } ],
  "growth": { "previous_revenue": 12.0, "previous_order_count": 1,
              "revenue_growth_pct": 62.5, "order_growth_pct": 100.0 },
  "best_day": { "date": "2026-10-01", "revenue": 10.0 }
}
```

Growth is `null` when the previous period had zero, because growth from zero is undefined.

### `POST /api/v1/analytics/products`

Request: one entry per order line (or pre-aggregated rows; both work).

```json
{ "limit": 5,
  "items": [ { "product_id": 1, "product_name": "Flat White", "category_name": "Coffee",
               "quantity": 3, "revenue": "13.50" } ] }
```

Response `data`: `total_revenue`, `products` (all products, ranked by units then revenue),
`best_sellers` and `lowest_sellers` (top and bottom `limit`), and `categories` (`quantity`,
`revenue`, `product_count`, `revenue_share_pct`). Each product has `product_id`,
`product_name`, `category_name`, `quantity`, `revenue`, and `revenue_share_pct`.

### `POST /api/v1/analytics/customers`

```json
{ "start_date": "2026-10-01", "end_date": "2026-10-31",
  "orders": [ { "order_id": 1, "customer_id": 1, "placed_at": "2026-10-02T09:00:00Z", "total": "10.00" } ],
  "first_order_dates": { "1": "2026-03-01" } }
```

`first_order_dates` gives each customer's first ever order date (Laravel computes this with
one aggregate query). A customer is **new** if that date falls inside the period, and a
**repeat** customer if they placed 2+ orders in the period.

Response `data`:

```json
{ "active_customers": 2, "new_customers": 1, "repeat_customers": 1, "repeat_rate_pct": 50.0,
  "average_spend_per_customer": 30.0, "average_orders_per_customer": 1.5,
  "average_days_between_orders": 4.0,
  "frequency_distribution": { "1": 1, "2-3": 1, "4-6": 0, "7+": 0 },
  "top_customers": [ { "customer_id": 1, "orders": 2, "spend": 40.0 } ] }
```

### `POST /api/v1/analytics/forecast`

```json
{ "horizon_days": 7,
  "history": [ { "date": "2026-09-01", "revenue": "812.40" } ] }
```

You need at least **14 days** of history (missing days inside the range count as zero).
`horizon_days` must be 1–30; the default is 7.

Response `data` (abridged):

```json
{
  "forecast": [ { "date": "2026-10-31", "revenue": 905.12, "lower_80": 771.0, "upper_80": 1039.24 } ],
  "total_forecast_revenue": 6390.55,
  "metadata": {
    "method": "OLS linear trend + additive day-of-week seasonality",
    "is_machine_learning": false,
    "window_days_used": 84, "trend_slope_per_day": 1.84, "trend_direction": "up",
    "seasonality_used": true, "weekday_offsets": { "mon": -61.2, "sat": 140.3 },
    "residual_std": 104.6,
    "backtest": { "holdout_days": 7, "mae": 88.1, "mape_pct": 9.7 },
    "limitations": [ "..." ]
  }
}
```

## Forecasting methodology (and its limits)

This is a **transparent statistical baseline, not machine learning**.

1. Turn the history into a continuous daily series, filling gaps with 0.
2. Keep the most recent **84 days** (12 weeks).
3. Fit an **ordinary least-squares straight line** (`numpy.polyfit`) of revenue against day index.
4. With 21+ days, compute an **additive day-of-week offset**: the mean residual for each
   weekday, centred so the offsets sum to zero. Cafés have strong weekday patterns, so this
   usually matters more than the trend.
5. Forecast = trend + weekday offset, clipped at 0.
6. **80% interval** = ±1.28 × the standard deviation of the in-sample residuals.
7. **Backtest:** refit without the last `horizon_days` days, predict them, and report MAE and MAPE.
   This shows how accurate the method has actually been on this data.

Limitations, which are also returned in `metadata.limitations`:

- It can't anticipate holidays, promotions, weather, or one-off events.
- It assumes the recent trend continues, so accuracy drops quickly beyond about a week.
- Closed days count as zero and pull the trend down.
- The interval assumes normal residuals and is approximate.

A natural next step would be Holt-Winters or a gradient-boosted model with a calendar of
holidays and promotions, validated on the same backtest.

## Deployment

```bash
pip install -r requirements.txt
ANALYTICS_ENV=production ANALYTICS_SERVICE_KEY=... \
  uvicorn app.main:app --host 127.0.0.1 --port 8001 --workers 2 --proxy-headers
```

- Bind to a private interface or network. Only Laravel needs to reach it.
- Run it under systemd or supervisord, or in a container; it holds no state, so it scales horizontally.
- Rotate the key by updating both `.env` files and restarting both services.
