# CafeFlow: Implementation Progress

Status legend: ✅ done and verified · 🟡 partly done · ⬜ not started

| Phase | Status |
|---|---|
| 1. Foundation (Laravel, MySQL, auth, roles) | ✅ |
| 2. Customer website | ✅ |
| 3. Admin dashboard | ✅ |
| 4. CMS | ✅ |
| 5. Python analytics + Laravel integration | ✅ |
| 6. Integrations (payments, mail, API docs) | ✅ (Stripe gateway untested against a live account) |
| 7. Testing and optimisation | ✅ |
| 8. Deployment and docs | ✅ |

Last updated: 2026-10-30.

---

## Environment

The machine had no PHP, Composer or MySQL. Rather than install them system-wide, the stack runs in Docker Compose (`docker-compose.yml`):

| Service | Image / command |
|---|---|
| app | PHP 8.4 (`docker/php/Dockerfile`), `php artisan serve` on :8000 |
| queue | same image, `php artisan queue:work --tries=3 --backoff=10` |
| mysql | MySQL 8.4, plus a `cafeflow_testing` database created by `docker/mysql/` |
| analytics | FastAPI service from `services/analytics/` on :8001 |
| mailpit | Local SMTP catcher, UI on :8025 |

Host tools used as they were: Node 24 / npm 11 (Vite build) and Python 3.15 (venv for running the analytics tests outside Docker).

---

## Phase 1: Foundation

- Laravel 13 with MySQL, and session auth (login, register, logout, password reset by email).
- Roles in their own table (`super_admin`, `manager`, `staff`, `customer`).
- An ability map (`app/Support/Permissions.php`) registered as Gates. Super Admin passes everything, and a deactivated account passes nothing.
- `EnsureAccountIsActive` signs out deactivated users on their next request. `EnsureCustomer` keeps team accounts out of checkout.
- Rate limits for API, auth, checkout, cart and webhooks.
- Lazy-loading and silently discarded attributes throw in development.
- `DatabaseSeeder` always seeds roles and default content. The demo seeders run only outside production, and `DemoUserSeeder` throws if it is called in production.
- `php artisan cafeflow:create-admin` creates the first Super Admin in production, prompting for the password.

## Phase 2: Customer website

- Home (hero, featured products, categories, promotion banner, testimonials, hours), menu with category filter and search, and product page with sizes, add-ons, gallery and reviews.
- Cart (session, AJAX) with promo code and a pickup/delivery choice.
- Checkout with saved addresses, cash or card, and order notes.
- Account area: profile, password, addresses, order history, live tracking page (polls every 15 s), cancel while pending, and retry payment.
- About, Privacy, Terms, FAQ and Contact pages, all from the CMS.

## Phase 3: Admin dashboard

- Dashboard KPIs for a date range: revenue, orders, average order value and customers, each compared with the previous period.
- Dashboard charts: revenue/orders, status breakdown. Also recent orders, best sellers and low stock. Staff get a queue-focused dashboard.
- Orders:
  - Filterable list (AJAX).
  - Detail page with status buttons (AJAX), a cancel-with-reason modal, claim/assign, a timeline, payments and stock movements.
  - Printable receipt.
- Products (sizes, add-ons, recipe, images), categories, inventory with adjustment modal and movement ledger, and suppliers.
- Customers, promotions, review moderation, team management and business settings.

## Phase 4: CMS

Homepage sections and opening hours, pages (Markdown), banners with schedules, FAQs (reorderable), testimonials, and a media library. Content is cached and the cache is cleared on save. Output is escaped. Markdown is rendered with HTML input disabled.

## Phase 5: Analytics

- FastAPI service with four reports (sales, products, customers, forecast).
- Strict Pydantic validation and shared-key auth with a constant-time comparison.
- The service is stateless and has no database access.
- Laravel `AnalyticsClient`: timeouts, retries for network errors and 5xx responses only, structured logging, and 5-minute caching.
- `AnalyticsService` falls back to sales totals computed from MySQL when Python is down.
- Admin analytics page; API proxy at `/api/v1/analytics/{report}`.

## Phase 6: Integrations

- **Payments.** A `PaymentGateway` interface with two implementations:
  - `MockGateway`: development only, disabled in production. It shows a labelled test page and sends HMAC-signed webhooks.
  - `StripeGateway`: Stripe Checkout over REST, verifying the `Stripe-Signature` header. *Not run against a real Stripe account.*
- **Webhook processing** is idempotent, enforced by a unique `(provider, event_id)` index, and a payment only moves from pending to a final state once.
- **Card data** is never received or stored.
- **Queued notifications:**
  - Order placed (customer email).
  - New order (team, in-app).
  - Status changed, payment received, welcome, password reset.
  - Low stock (managers, at most once per 12 h per item).
- **Mail** goes to Mailpit locally. Verified 2026-10-30: the worker processed the queued jobs and Mailpit received them. Emails are sent in the café's business name.
- **API:** REST API `/api/v1` using the `{success, message, data}` envelope, with Sanctum tokens. OpenAPI spec at `docs/openapi.yaml`, Swagger UI at `/docs/api` (bundled locally, no CDN).

## Phase 7: Testing

**Executed 2026-10-30:**

- `docker compose exec app php artisan test`: **64 passed (258 assertions)**, about 10 s, against MySQL `cafeflow_testing`.
- `services/analytics/.venv/bin/python -m pytest -q`: **29 passed** (1 harmless Starlette deprecation warning).

| File | Covers |
|---|---|
| `AuthTest` | registration is always a customer, login/logout, deactivated users, no demo seeding in production |
| `AccessControlTest` | guest / customer / staff / manager / super admin access to admin screens |
| `Admin/CatalogManagementTest` | product with sizes, add-ons and recipe; validation; archive and delete; category CRUD; staff blocked |
| `Admin/ContentManagementTest` | published vs draft FAQ on the site, HTML escaping, staff blocked |
| `OrderPricingTest` | server-side totals, tax, delivery fee and minimum, unavailable products, foreign add-ons, notifications |
| `InventoryDeductionTest` | deduction on confirm (scaled by size and add-ons), insufficient stock rolls back, refused at checkout, reversal on cancel, no double deduction, illegal transitions, unpaid card orders, one low-stock alert |
| `PromotionTest` | percentage/fixed/cap maths, inactive / expired / not started / minimum order, per-customer and global limits, cancelling frees the use |
| `CheckoutTest` | guest redirect, cash checkout through HTTP, team accounts blocked, card → test gateway → signed webhook → paid, other customers' orders hidden |
| `PaymentWebhookTest` | missing, wrong and expired signatures; tampered body; redelivery processed once; a late failure can't undo success; no card columns |
| `Api/ApiTest` | token login, error envelopes (401/404/422), drafts hidden, order placement with server pricing, staff need a claimed order, role-gated endpoints |
| `AnalyticsIntegrationTest` | service key sent server-side and never returned, fallback (503 + `laravel-fallback`), admin page renders with the service down |
| `Unit/OrderStatusTest` | pickup and delivery lifecycles, cents arithmetic |

**Bugs the tests found, now fixed:**

- `admin/analytics.blade.php`: `period@if (...)` wasn't compiled as a directive (Blade needs a non-word character before `@`), but its `@endif` was. The page threw a ParseError. Rewritten without the inline directive, and every view was checked for the same pattern.
- `OrderService::place`: an order whose only product had become unavailable said "Your cart is empty" instead of naming the product. It now checks for issues first.

**Other fixes in this phase:**

- Customer emails and the mail layout used `APP_NAME` ("CafeFlow"). They now use the café's name from the CMS (`cafeflow.business_name`).
- Pagination printed its summary twice.
- "1 items" is now "1 item".

**Not run:** HawkScan DAST. The HawkScan hook is installed, but `HAWK_API_KEY` is not set on this machine, so no scan could run. Run one before going live.

## Phase 8: Deployment and docs

- `README.md`: setup, demo accounts, architecture and ER diagrams, permissions, order/stock/payment rules, email, API, tests, configuration, production deployment, known limitations.
- `docs/openapi.yaml` and `docs/screenshots/` (desktop and 390 px mobile, captured from the running app).
- Git repository initialised with commits by area.
