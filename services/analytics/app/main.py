import logging
import time

from fastapi import Depends, FastAPI, Request, status
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from starlette.exceptions import HTTPException as StarletteHTTPException

from app import __version__
from app.analytics.customers import analyze_customers
from app.analytics.forecast import InsufficientHistory, forecast_revenue
from app.analytics.products import analyze_products
from app.analytics.sales import analyze_sales
from app.config import get_settings
from app.schemas import CustomersRequest, Envelope, ForecastRequest, ProductsRequest, SalesRequest
from app.security import require_service_key

settings = get_settings()
logging.basicConfig(level=settings.log_level, format="%(asctime)s %(levelname)s %(name)s %(message)s")
log = logging.getLogger("cafeflow.analytics")

app = FastAPI(
    title="CafeFlow Analytics Service",
    version=__version__,
    description="Stateless analytics for CafeFlow. Laravel stays the source of truth: this service "
    "only computes results from the data it is sent and never writes business records.",
    docs_url="/docs" if settings.env != "production" else None,
    redoc_url=None,
)
STARTED_AT = time.time()


def ok(data, message: str = "OK") -> Envelope:
    return Envelope(success=True, message=message, data=data)


# ---------- error handling: same envelope as the Laravel API ----------

@app.exception_handler(StarletteHTTPException)
async def http_error(_: Request, exc: StarletteHTTPException):
    return JSONResponse({"success": False, "message": exc.detail, "data": None}, status_code=exc.status_code)


@app.exception_handler(RequestValidationError)
async def validation_error(_: Request, exc: RequestValidationError):
    errors = [{"field": ".".join(str(p) for p in e["loc"][1:]), "message": e["msg"]} for e in exc.errors()]
    return JSONResponse(
        {"success": False, "message": "The given data was invalid.", "errors": errors[:50]},
        status_code=status.HTTP_422_UNPROCESSABLE_CONTENT,
    )


@app.exception_handler(Exception)
async def unhandled_error(request: Request, exc: Exception):
    log.exception("Unhandled error on %s", request.url.path)
    return JSONResponse({"success": False, "message": "Internal analytics error", "data": None}, status_code=500)


@app.middleware("http")
async def access_log(request: Request, call_next):
    start = time.perf_counter()
    response = await call_next(request)
    log.info("%s %s -> %s (%.1f ms)", request.method, request.url.path, response.status_code,
             (time.perf_counter() - start) * 1000)
    return response


# ---------- routes ----------

@app.get("/health", response_model=Envelope)
def health():
    return ok({
        "status": "ok",
        "service": "cafeflow-analytics",
        "version": __version__,
        "uptime_seconds": round(time.time() - STARTED_AT, 1),
        "auth_configured": bool(settings.service_key) and settings.service_key != "change-me",
    })


v1 = [Depends(require_service_key)]


@app.post("/api/v1/analytics/sales", response_model=Envelope, dependencies=v1)
def sales(req: SalesRequest):
    return ok(analyze_sales(req), "Sales analytics calculated")


@app.post("/api/v1/analytics/products", response_model=Envelope, dependencies=v1)
def products(req: ProductsRequest):
    return ok(analyze_products(req), "Product analytics calculated")


@app.post("/api/v1/analytics/customers", response_model=Envelope, dependencies=v1)
def customers(req: CustomersRequest):
    return ok(analyze_customers(req), "Customer analytics calculated")


@app.post("/api/v1/analytics/forecast", response_model=Envelope, dependencies=v1)
def forecast(req: ForecastRequest):
    try:
        return ok(forecast_revenue(req), "Forecast generated")
    except InsufficientHistory as e:
        return JSONResponse(
            {"success": False, "message": str(e), "errors": [{"field": "history", "message": str(e)}]},
            status_code=status.HTTP_422_UNPROCESSABLE_CONTENT,
        )
