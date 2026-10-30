import pandas as pd

from app.schemas import SalesRequest


def _growth_pct(current: float, previous: float) -> float | None:
    if previous == 0:
        return None  # undefined, not "infinite growth"
    return round((current - previous) / previous * 100, 2)


def analyze_sales(req: SalesRequest) -> dict:
    days = pd.date_range(req.start_date, req.end_date, freq="D")

    if req.orders:
        df = pd.DataFrame(
            {"date": [o.placed_at.date() for o in req.orders], "total": [float(o.total) for o in req.orders]}
        )
        df["date"] = pd.to_datetime(df["date"])
        df = df[(df["date"] >= days[0]) & (df["date"] <= days[-1])]
    else:
        df = pd.DataFrame({"date": pd.Series(dtype="datetime64[ns]"), "total": pd.Series(dtype=float)})

    daily = df.groupby("date")["total"].agg(["sum", "count"]).reindex(days, fill_value=0)

    revenue = float(daily["sum"].sum())
    orders = int(daily["count"].sum())
    prev_revenue = float(sum(float(o.total) for o in req.previous_orders))
    prev_orders = len(req.previous_orders)

    return {
        "total_revenue": round(revenue, 2),
        "order_count": orders,
        "average_order_value": round(revenue / orders, 2) if orders else 0.0,
        "revenue_by_date": [
            {"date": d.date().isoformat(), "revenue": round(float(r["sum"]), 2), "orders": int(r["count"])}
            for d, r in daily.iterrows()
        ],
        "growth": {
            "previous_revenue": round(prev_revenue, 2),
            "previous_order_count": prev_orders,
            "revenue_growth_pct": _growth_pct(revenue, prev_revenue),
            "order_growth_pct": _growth_pct(orders, prev_orders),
        },
        "best_day": (
            {"date": daily["sum"].idxmax().date().isoformat(), "revenue": round(float(daily["sum"].max()), 2)}
            if orders
            else None
        ),
    }
