from datetime import date, timedelta


def history(days, fn, start=date(2026, 8, 3)):  # 2026-08-03 is a Monday
    return [{"date": (start + timedelta(days=i)).isoformat(), "revenue": f"{fn(i):.2f}"} for i in range(days)]


def test_forecast_follows_linear_trend(client, auth):
    r = client.post("/api/v1/analytics/forecast", headers=auth,
                    json={"history": history(28, lambda i: 100 + 10 * i), "horizon_days": 7})
    assert r.status_code == 200, r.text
    d = r.json()["data"]
    assert len(d["forecast"]) == 7
    assert d["forecast"][0]["date"] == "2026-08-31"
    # A perfect line continues exactly: day 28 -> 380
    assert abs(d["forecast"][0]["revenue"] - 380) < 0.5
    assert abs(d["forecast"][6]["revenue"] - 440) < 0.5
    meta = d["metadata"]
    assert meta["is_machine_learning"] is False
    assert meta["trend_direction"] == "up"
    assert abs(meta["trend_slope_per_day"] - 10) < 0.01
    assert meta["backtest"]["mae"] < 0.5
    assert meta["limitations"]


def test_forecast_captures_weekday_pattern(client, auth):
    # Flat 200/day, weekends +100
    r = client.post("/api/v1/analytics/forecast", headers=auth,
                    json={"history": history(56, lambda i: 300 if i % 7 in (5, 6) else 200)})
    d = r.json()["data"]
    by_day = {date.fromisoformat(f["date"]).weekday(): f["revenue"] for f in d["forecast"]}
    assert by_day[5] - by_day[2] > 90
    assert d["metadata"]["seasonality_used"] is True
    for f in d["forecast"]:
        assert f["lower_80"] <= f["revenue"] <= f["upper_80"]


def test_forecast_never_negative(client, auth):
    r = client.post("/api/v1/analytics/forecast", headers=auth,
                    json={"history": history(20, lambda i: max(0, 300 - 20 * i))})
    assert all(f["revenue"] >= 0 and f["lower_80"] >= 0 for f in r.json()["data"]["forecast"])


def test_forecast_fills_missing_days(client, auth):
    h = history(30, lambda i: 100)
    del h[10:13]
    r = client.post("/api/v1/analytics/forecast", headers=auth, json={"history": h})
    assert r.status_code == 200
    assert r.json()["data"]["metadata"]["history_days_received"] == 30


def test_forecast_requires_minimum_history(client, auth):
    r = client.post("/api/v1/analytics/forecast", headers=auth, json={"history": history(10, lambda i: 100)})
    assert r.status_code == 422
    assert "14 days" in r.json()["message"]


def test_forecast_rejects_duplicate_dates_and_bad_horizon(client, auth):
    h = history(20, lambda i: 100)
    r = client.post("/api/v1/analytics/forecast", headers=auth, json={"history": h + h[:1]})
    assert r.status_code == 422
    r = client.post("/api/v1/analytics/forecast", headers=auth, json={"history": h, "horizon_days": 31})
    assert r.status_code == 422


def test_forecast_rejects_empty_history(client, auth):
    r = client.post("/api/v1/analytics/forecast", headers=auth, json={"history": []})
    assert r.status_code == 422
