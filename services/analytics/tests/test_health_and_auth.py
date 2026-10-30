import pytest

from app.config import Settings, get_settings
from app.main import app

ENDPOINTS = ["sales", "products", "customers", "forecast"]


def test_health_is_public(client):
    r = client.get("/health")
    assert r.status_code == 200
    body = r.json()
    assert body["success"] is True
    assert body["data"]["status"] == "ok"
    assert body["data"]["auth_configured"] is True
    assert "test-key" not in r.text


@pytest.mark.parametrize("name", ENDPOINTS)
def test_missing_key_rejected(client, name):
    r = client.post(f"/api/v1/analytics/{name}", json={})
    assert r.status_code == 401
    assert r.json() == {"success": False, "message": "Invalid or missing service key", "data": None}


@pytest.mark.parametrize("name", ENDPOINTS)
def test_wrong_key_rejected(client, name):
    r = client.post(f"/api/v1/analytics/{name}", json={}, headers={"X-Service-Key": "nope"})
    assert r.status_code == 401


def test_unconfigured_key_returns_503(client):
    app.dependency_overrides[get_settings] = lambda: Settings(service_key="change-me")
    try:
        r = client.post("/api/v1/analytics/sales", json={}, headers={"X-Service-Key": "change-me"})
        assert r.status_code == 503
    finally:
        app.dependency_overrides.clear()


def test_unknown_route_uses_envelope(client):
    r = client.get("/nope")
    assert r.status_code == 404
    assert r.json()["success"] is False
