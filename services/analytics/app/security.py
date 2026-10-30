import hmac

from fastapi import Depends, Header, HTTPException, status

from app.config import Settings, get_settings


def require_service_key(
    x_service_key: str | None = Header(default=None),
    settings: Settings = Depends(get_settings),
) -> None:
    """Authenticate the calling service (Laravel) with a shared secret.

    The comparison is constant-time so the key can't be recovered by timing.
    """
    if not settings.service_key or settings.service_key == "change-me":
        # Refuse to serve analytics with no secret or the placeholder one.
        raise HTTPException(status.HTTP_503_SERVICE_UNAVAILABLE, "Service key is not configured")

    if not x_service_key or not hmac.compare_digest(x_service_key, settings.service_key):
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Invalid or missing service key")
