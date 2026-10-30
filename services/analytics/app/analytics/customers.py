import pandas as pd

from app.schemas import CustomersRequest


def analyze_customers(req: CustomersRequest) -> dict:
    empty = {
        "active_customers": 0,
        "new_customers": 0,
        "repeat_customers": 0,
        "repeat_rate_pct": 0.0,
        "average_spend_per_customer": 0.0,
        "average_orders_per_customer": 0.0,
        "average_days_between_orders": None,
        "frequency_distribution": {"1": 0, "2-3": 0, "4-6": 0, "7+": 0},
        "top_customers": [],
    }
    if not req.orders:
        return empty

    df = pd.DataFrame(
        {
            "customer_id": [o.customer_id for o in req.orders],
            "placed_at": [o.placed_at for o in req.orders],
            "total": [float(o.total) for o in req.orders],
        }
    )
    df["placed_at"] = pd.to_datetime(df["placed_at"], utc=True)
    df["day"] = df["placed_at"].dt.date
    in_period = df[(df["day"] >= req.start_date) & (df["day"] <= req.end_date)]
    if in_period.empty:
        return empty

    per_customer = in_period.groupby("customer_id").agg(orders=("total", "size"), spend=("total", "sum"))

    # "New" = first ever order falls inside the period. Laravel supplies the true
    # first-order date; without it we fall back to the earliest order we were sent.
    earliest_seen = df.groupby("customer_id")["day"].min().to_dict()
    first_dates = {cid: req.first_order_dates.get(cid, earliest_seen[cid]) for cid in per_customer.index}
    new_ids = [cid for cid, d in first_dates.items() if req.start_date <= d <= req.end_date]

    repeat_ids = per_customer.index[per_customer["orders"] >= 2]

    # Mean gap between consecutive orders, for customers with 2+ orders in the period.
    gaps = (
        in_period.sort_values("placed_at")
        .groupby("customer_id")["placed_at"]
        .diff()
        .dropna()
        .dt.total_seconds()
        / 86400
    )

    bins = pd.cut(per_customer["orders"], bins=[0, 1, 3, 6, float("inf")], labels=["1", "2-3", "4-6", "7+"])
    distribution = {str(k): int(v) for k, v in bins.value_counts().reindex(["1", "2-3", "4-6", "7+"]).items()}

    active = len(per_customer)
    top = per_customer.sort_values(["spend", "orders"], ascending=False).head(5).reset_index()

    return {
        "active_customers": active,
        "new_customers": len(new_ids),
        "repeat_customers": int(len(repeat_ids)),
        "repeat_rate_pct": round(len(repeat_ids) / active * 100, 2),
        "average_spend_per_customer": round(float(per_customer["spend"].mean()), 2),
        "average_orders_per_customer": round(float(per_customer["orders"].mean()), 2),
        "average_days_between_orders": round(float(gaps.mean()), 2) if not gaps.empty else None,
        "frequency_distribution": distribution,
        "top_customers": [
            {"customer_id": int(r.customer_id), "orders": int(r.orders), "spend": round(float(r.spend), 2)}
            for r in top.itertuples()
        ],
    }
