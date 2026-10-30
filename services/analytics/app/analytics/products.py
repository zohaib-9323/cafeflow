import pandas as pd

from app.schemas import ProductsRequest


def analyze_products(req: ProductsRequest) -> dict:
    if not req.items:
        return {"products": [], "best_sellers": [], "lowest_sellers": [], "categories": [], "total_revenue": 0.0}

    df = pd.DataFrame(
        {
            "product_id": [i.product_id for i in req.items],
            "product_name": [i.product_name for i in req.items],
            "category_name": [i.category_name for i in req.items],
            "quantity": [i.quantity for i in req.items],
            "revenue": [float(i.revenue) for i in req.items],
        }
    )
    total_revenue = float(df["revenue"].sum())

    products = (
        df.groupby("product_id")
        .agg(product_name=("product_name", "last"), category_name=("category_name", "last"),
             quantity=("quantity", "sum"), revenue=("revenue", "sum"))
        .reset_index()
    )
    products["revenue_share_pct"] = (products["revenue"] / total_revenue * 100) if total_revenue else 0.0
    # Rank by units sold, break ties on revenue, then id so output is deterministic.
    products = products.sort_values(["quantity", "revenue", "product_id"], ascending=[False, False, True])

    categories = (
        df.groupby("category_name")
        .agg(quantity=("quantity", "sum"), revenue=("revenue", "sum"), product_count=("product_id", "nunique"))
        .reset_index()
        .sort_values("revenue", ascending=False)
    )
    categories["revenue_share_pct"] = (categories["revenue"] / total_revenue * 100) if total_revenue else 0.0

    def records(frame: pd.DataFrame) -> list[dict]:
        out = frame.round({"revenue": 2, "revenue_share_pct": 2}).to_dict(orient="records")
        for row in out:
            for k, v in row.items():
                if hasattr(v, "item"):
                    row[k] = v.item()  # numpy scalar -> python
        return out

    lowest = products.sort_values(["quantity", "revenue", "product_id"], ascending=[True, True, True])

    return {
        "total_revenue": round(total_revenue, 2),
        "products": records(products),
        "best_sellers": records(products.head(req.limit)),
        "lowest_sellers": records(lowest.head(req.limit)),
        "categories": records(categories),
    }
