def o(i, cid, at, total):
    return {"order_id": i, "customer_id": cid, "placed_at": at, "total": total}


def test_customer_segments(client, auth):
    payload = {
        "start_date": "2026-10-01",
        "end_date": "2026-10-31",
        "orders": [
            o(1, 1, "2026-10-02T09:00:00Z", "10.00"),
            o(2, 1, "2026-10-06T09:00:00Z", "30.00"),   # customer 1: repeat, gap 4 days
            o(3, 2, "2026-10-10T09:00:00Z", "20.00"),   # customer 2: single order
            o(4, 3, "2026-09-15T09:00:00Z", "50.00"),   # customer 3: ordered before the period only
        ],
        "first_order_dates": {"1": "2026-03-01"},       # customer 1 is long-standing
    }
    r = client.post("/api/v1/analytics/customers", json=payload, headers=auth)
    assert r.status_code == 200, r.text
    d = r.json()["data"]
    assert d["active_customers"] == 2
    assert d["new_customers"] == 1          # only customer 2
    assert d["repeat_customers"] == 1
    assert d["repeat_rate_pct"] == 50.0
    assert d["average_spend_per_customer"] == 30.0
    assert d["average_orders_per_customer"] == 1.5
    assert d["average_days_between_orders"] == 4.0
    assert d["frequency_distribution"] == {"1": 1, "2-3": 1, "4-6": 0, "7+": 0}
    assert d["top_customers"][0] == {"customer_id": 1, "orders": 2, "spend": 40.0}


def test_customers_requires_customer_id(client, auth):
    payload = {"start_date": "2026-10-01", "end_date": "2026-10-31",
               "orders": [{"order_id": 1, "placed_at": "2026-10-02T09:00:00Z", "total": "1.00"}]}
    r = client.post("/api/v1/analytics/customers", json=payload, headers=auth)
    assert r.status_code == 422
    assert any(e["field"].endswith("customer_id") for e in r.json()["errors"])


def test_customers_empty(client, auth):
    r = client.post("/api/v1/analytics/customers", headers=auth,
                    json={"start_date": "2026-10-01", "end_date": "2026-10-31", "orders": []})
    assert r.json()["data"]["active_customers"] == 0
