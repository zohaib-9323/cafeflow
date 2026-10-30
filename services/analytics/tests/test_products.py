def item(pid, name, cat, qty, rev):
    return {"product_id": pid, "product_name": name, "category_name": cat, "quantity": qty, "revenue": rev}


def test_product_ranking_and_categories(client, auth):
    items = [
        item(1, "Flat White", "Coffee", 3, "13.50"),
        item(1, "Flat White", "Coffee", 2, "9.00"),
        item(2, "Croissant", "Bakery", 4, "14.00"),
        item(3, "Matcha Latte", "Tea", 1, "5.00"),
    ]
    r = client.post("/api/v1/analytics/products", json={"items": items, "limit": 2}, headers=auth)
    assert r.status_code == 200, r.text
    d = r.json()["data"]
    assert d["total_revenue"] == 41.5
    assert [p["product_id"] for p in d["best_sellers"]] == [1, 2]
    assert d["best_sellers"][0]["quantity"] == 5 and d["best_sellers"][0]["revenue"] == 22.5
    assert [p["product_id"] for p in d["lowest_sellers"]] == [3, 2]
    coffee = next(c for c in d["categories"] if c["category_name"] == "Coffee")
    assert coffee["revenue_share_pct"] == 54.22
    assert d["categories"][0]["category_name"] == "Coffee"  # sorted by revenue


def test_products_empty(client, auth):
    r = client.post("/api/v1/analytics/products", json={"items": []}, headers=auth)
    assert r.status_code == 200
    assert r.json()["data"]["best_sellers"] == []


def test_products_limit_validated(client, auth):
    r = client.post("/api/v1/analytics/products", json={"items": [], "limit": 0}, headers=auth)
    assert r.status_code == 422
