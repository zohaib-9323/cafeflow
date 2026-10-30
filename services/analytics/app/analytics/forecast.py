"""Short-horizon revenue forecast.

Method: an ordinary least-squares linear trend over the recent window, plus an
additive day-of-week adjustment (the mean residual for each weekday). It is a
transparent statistical baseline, not a machine-learning model. The response
says so and includes a backtest so callers can judge how accurate it is.
"""

from datetime import date, timedelta

import numpy as np
import pandas as pd

from app.schemas import ForecastRequest

MIN_HISTORY_DAYS = 14
MAX_WINDOW_DAYS = 84  # 12 weeks: recent enough to follow the trend, long enough for weekday means
SEASONALITY_MIN_DAYS = 21  # need at least 3 observations of each weekday
Z_80 = 1.2816

LIMITATIONS = [
    "Linear trend plus weekday averages: it cannot anticipate holidays, promotions or one-off events.",
    "Assumes the recent trend continues. Accuracy drops quickly beyond about a week.",
    "Days with no orders are counted as zero revenue, so a closure lowers the trend.",
    "The 80% interval assumes normally distributed residuals and is approximate.",
]


class InsufficientHistory(ValueError):
    pass


def _daily_series(req: ForecastRequest) -> pd.Series:
    s = pd.Series({pd.Timestamp(h.date): float(h.revenue) for h in req.history}).sort_index()
    full = pd.date_range(s.index.min(), s.index.max(), freq="D")
    return s.reindex(full, fill_value=0.0)


def _fit(y: np.ndarray, weekdays: np.ndarray) -> tuple[np.ndarray, np.ndarray, bool]:
    """Return (trend coefficients, per-weekday additive offsets, seasonality_used)."""
    t = np.arange(len(y), dtype=float)
    coef = np.polyfit(t, y, 1)
    residual = y - np.polyval(coef, t)

    seasonal = np.zeros(7)
    used = len(y) >= SEASONALITY_MIN_DAYS
    if used:
        for d in range(7):
            mask = weekdays == d
            if mask.any():
                seasonal[d] = residual[mask].mean()
        seasonal -= seasonal.mean()  # offsets sum to zero, so the trend keeps the level
    return coef, seasonal, used


def _predict(coef, seasonal, start_t: int, start_day: date, n: int) -> tuple[np.ndarray, list[date]]:
    t = np.arange(start_t, start_t + n, dtype=float)
    days = [start_day + timedelta(days=i) for i in range(n)]
    dow = np.array([d.weekday() for d in days])
    return np.clip(np.polyval(coef, t) + seasonal[dow], 0, None), days


def _backtest(y: np.ndarray, weekdays: np.ndarray, horizon: int) -> dict | None:
    if len(y) < MIN_HISTORY_DAYS + horizon:
        return None
    train_y, test_y = y[:-horizon], y[-horizon:]
    coef, seasonal, _ = _fit(train_y, weekdays[:-horizon])
    t = np.arange(len(train_y), len(y), dtype=float)
    pred = np.clip(np.polyval(coef, t) + seasonal[weekdays[-horizon:]], 0, None)
    err = pred - test_y
    nonzero = test_y > 0
    mape = float(np.mean(np.abs(err[nonzero]) / test_y[nonzero]) * 100) if nonzero.any() else None
    return {
        "holdout_days": horizon,
        "mae": round(float(np.mean(np.abs(err))), 2),
        "mape_pct": round(mape, 2) if mape is not None else None,
    }


def forecast_revenue(req: ForecastRequest) -> dict:
    series = _daily_series(req)
    if len(series) < MIN_HISTORY_DAYS:
        raise InsufficientHistory(
            f"At least {MIN_HISTORY_DAYS} days of history are required (got {len(series)})."
        )

    window = series.iloc[-MAX_WINDOW_DAYS:]
    y = window.to_numpy()
    weekdays = np.array([d.weekday() for d in window.index])

    coef, seasonal, seasonality_used = _fit(y, weekdays)
    fitted = np.polyval(coef, np.arange(len(y))) + seasonal[weekdays]
    resid_std = float(np.std(y - fitted, ddof=2)) if len(y) > 2 else 0.0

    last_day = window.index[-1].date()
    pred, days = _predict(coef, seasonal, len(y), last_day + timedelta(days=1), req.horizon_days)
    margin = Z_80 * resid_std

    recent_avg = float(y[-7:].mean())
    return {
        "forecast": [
            {
                "date": d.isoformat(),
                "revenue": round(float(p), 2),
                "lower_80": round(max(0.0, float(p) - margin), 2),
                "upper_80": round(float(p) + margin, 2),
            }
            for d, p in zip(days, pred)
        ],
        "total_forecast_revenue": round(float(pred.sum()), 2),
        "metadata": {
            "method": "OLS linear trend + additive day-of-week seasonality",
            "is_machine_learning": False,
            "history_days_received": len(series),
            "window_days_used": len(y),
            "window_start": window.index[0].date().isoformat(),
            "window_end": last_day.isoformat(),
            "trend_slope_per_day": round(float(coef[0]), 2),
            "trend_direction": "up" if coef[0] > 0.5 else "down" if coef[0] < -0.5 else "flat",
            "recent_7_day_average": round(recent_avg, 2),
            "seasonality_used": seasonality_used,
            "weekday_offsets": (
                {name: round(float(v), 2) for name, v in
                 zip(["mon", "tue", "wed", "thu", "fri", "sat", "sun"], seasonal)}
                if seasonality_used else None
            ),
            "residual_std": round(resid_std, 2),
            "backtest": _backtest(y, weekdays, req.horizon_days),
            "limitations": LIMITATIONS,
        },
    }
