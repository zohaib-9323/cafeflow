"""Request and response models.

Money arrives as decimal strings or numbers from Laravel. Pydantic parses it into
Decimal so bad values are rejected, and the analytics modules turn it into floats
for vectorised maths. Results are rounded to 2 dp.
"""

from datetime import date, datetime
from decimal import Decimal
from typing import Annotated, Any

from pydantic import BaseModel, ConfigDict, Field, model_validator

Money = Annotated[Decimal, Field(ge=0, max_digits=14, decimal_places=2)]
Qty = Annotated[int, Field(ge=0, le=1_000_000)]
Id = Annotated[int, Field(ge=1)]

MAX_RECORDS = 200_000


class Strict(BaseModel):
    model_config = ConfigDict(extra="forbid")


class Envelope(BaseModel):
    success: bool = True
    message: str = "OK"
    data: Any = None


# ---------- sales ----------

class OrderRecord(Strict):
    order_id: Id
    placed_at: datetime
    total: Money
    customer_id: Id | None = None


class SalesRequest(Strict):
    orders: list[OrderRecord] = Field(max_length=MAX_RECORDS)
    previous_orders: list[OrderRecord] = Field(default_factory=list, max_length=MAX_RECORDS)
    start_date: date
    end_date: date

    @model_validator(mode="after")
    def check_range(self):
        if self.end_date < self.start_date:
            raise ValueError("end_date must be on or after start_date")
        if (self.end_date - self.start_date).days > 366 * 2:
            raise ValueError("date range may not exceed two years")
        return self


# ---------- products ----------

class LineItemRecord(Strict):
    product_id: Id
    product_name: str = Field(min_length=1, max_length=255)
    category_name: str = Field(min_length=1, max_length=255)
    quantity: Qty
    revenue: Money


class ProductsRequest(Strict):
    items: list[LineItemRecord] = Field(max_length=MAX_RECORDS)
    limit: int = Field(default=5, ge=1, le=50)


# ---------- customers ----------

class CustomerOrderRecord(Strict):
    order_id: Id
    customer_id: Id
    placed_at: datetime
    total: Money


class CustomersRequest(Strict):
    orders: list[CustomerOrderRecord] = Field(max_length=MAX_RECORDS)
    start_date: date
    end_date: date
    first_order_dates: dict[int, date] = Field(
        default_factory=dict,
        description="Customer id -> date of their first ever order. Customers missing here "
        "are treated as having first ordered at their earliest order in `orders`.",
    )

    @model_validator(mode="after")
    def check_range(self):
        if self.end_date < self.start_date:
            raise ValueError("end_date must be on or after start_date")
        return self


# ---------- forecast ----------

class DailyRevenue(Strict):
    date: date
    revenue: Money


class ForecastRequest(Strict):
    history: list[DailyRevenue] = Field(min_length=1, max_length=3660)
    horizon_days: int = Field(default=7, ge=1, le=30)

    @model_validator(mode="after")
    def unique_dates(self):
        dates = [h.date for h in self.history]
        if len(dates) != len(set(dates)):
            raise ValueError("history contains duplicate dates")
        return self
