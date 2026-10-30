def order(i, at, total, customer=None):
    return {"order_id": i, "placed_at": at, "total": total, "customer_id": customer}


def test_sales_totals_and_daily_series(client, auth):
    payload = {
        "start_date": "2026-10-01",
        "end_date": "2026-10-03",
        "orders": [
            order(1, "2026-10-01T09:00:00Z", "10.00"),
            order(2, "2026-10-01T12:30:00Z", "20.50"),
            order(3, "2026-10-03T08:00:00Z", "9.50"),
            order(4, "2026-09-28T08:00:00Z", "999.00"),  # outside range, ignored
        ],
        "previous_orders": [order(10, "2026-09-28T09:00:00Z", "20.00")],
    }
    r = client.post("/api/v1/analytics/sales", json=payload, headers=auth)
    assert r.status_code == 200, r.text
    d = r.json()["data"]
    assert d["total_revenue"] == 40.0
    assert d["order_count"] == 3
    assert d["average_order_value"] == 13.33
    assert [x["revenue"] for x in d["revenue_by_date"]] == [30.5, 0.0, 9.5]  # gap day filled with zero
    assert d["growth"]["revenue_growth_pct"] == 100.0
    assert d["growth"]["order_growth_pct"] == 200.0
    assert d["best_day"] == {"date": "2026-10-01", "revenue": 30.5}


def test_sales_empty_period(client, auth):
    r = client.post("/api/v1/analytics/sales", headers=auth,
                    json={"start_date": "2026-10-01", "end_date": "2026-10-02", "orders": []})
    d = r.json()["data"]
    assert d["total_revenue"] == 0 and d["order_count"] == 0 and d["average_order_value"] == 0
    assert d["growth"]["revenue_growth_pct"] is None  # no previous revenue: growth undefined
    assert d["best_day"] is None


def test_sales_rejects_inverted_range(client, auth):
    r = client.post("/api/v1/analytics/sales", headers=auth,
                    json={"start_date": "2026-10-05", "end_date": "2026-10-01", "orders": []})
    assert r.status_code == 422
    assert r.json()["success"] is False


def test_sales_rejects_negative_and_malformed_money(client, auth):
    base = {"start_date": "2026-10-01", "end_date": "2026-10-01"}
    for bad in ["-1.00", "abc", "1.234"]:
        r = client.post("/api/v1/analytics/sales", headers=auth,
                        json={**base, "orders": [order(1, "2026-10-01T09:00:00Z", bad)]})
        assert r.status_code == 422, bad


def test_sales_rejects_unknown_fields(client, auth):
    r = client.post("/api/v1/analytics/sales", headers=auth,
                    json={"start_date": "2026-10-01", "end_date": "2026-10-01", "orders": [], "drop": 1})
    assert r.status_code == 422
