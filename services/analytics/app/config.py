from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", env_prefix="ANALYTICS_", extra="ignore")

    service_key: str = ""
    env: str = "local"
    log_level: str = "INFO"
    max_records: int = 200_000


@lru_cache
def get_settings() -> Settings:
    return Settings()
