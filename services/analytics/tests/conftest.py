import os

os.environ["ANALYTICS_SERVICE_KEY"] = "test-key"

import pytest  # noqa: E402
from fastapi.testclient import TestClient  # noqa: E402

from app.main import app  # noqa: E402

AUTH = {"X-Service-Key": "test-key"}


@pytest.fixture
def client():
    return TestClient(app)


@pytest.fixture
def auth():
    return AUTH
