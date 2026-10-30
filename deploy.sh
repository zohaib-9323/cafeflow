#!/usr/bin/env bash
# Deploys the CafeFlow demo on a single Linux server with Docker (e.g. an Oracle Cloud free VM).
#   ./deploy.sh                    -> https://<public-ip>.sslip.io
#   ./deploy.sh demo.example.com   -> your own domain (point its A record at this server first)
# Safe to run again after `git pull`: secrets and data are kept, only code, assets and caches are refreshed.
set -euo pipefail
cd "$(dirname "$0")"

if [ ! -f .env ]; then
    cp .env.example .env
    # APP_ENV=staging, not production: production skips the demo data and disables the test payment gateway.
    sed -i \
        -e "s|^APP_ENV=.*|APP_ENV=staging|" \
        -e "s|^APP_DEBUG=.*|APP_DEBUG=false|" \
        -e "s|^LOG_LEVEL=.*|LOG_LEVEL=warning|" \
        -e "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|" \
        -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -hex 24)|" \
        -e "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=$(openssl rand -hex 24)|" \
        -e "s|^ANALYTICS_SERVICE_KEY=.*|ANALYTICS_SERVICE_KEY=$(openssl rand -hex 32)|" \
        -e "s|^MOCK_PAYMENT_WEBHOOK_SECRET=.*|MOCK_PAYMENT_WEBHOOK_SECRET=$(openssl rand -hex 32)|" \
        .env
    cat >> .env <<'EOF'

# --- Demo server (written by deploy.sh) ---
TRUSTED_PROXIES=*
COMPOSE_FILE=docker-compose.yml:docker-compose.demo.yml
SITE_ADDRESS=
EOF
fi

site="${1:-$(grep '^SITE_ADDRESS=' .env | cut -d= -f2-)}"
if [ -z "$site" ]; then
    ip="$(curl -fsS https://api.ipify.org)"
    site="${ip//./-}.sslip.io"
fi
sed -i -e "s|^SITE_ADDRESS=.*|SITE_ADDRESS=$site|" -e "s|^APP_URL=.*|APP_URL=https://$site|" .env

artisan() { docker compose run --rm -T app php artisan "$@"; }

echo "==> Building images and starting MySQL"
docker compose build
docker compose up -d --wait mysql

echo "==> Installing PHP packages (dev packages included: the demo seeders use Faker)"
docker compose run --rm -T app composer install --no-interaction --optimize-autoloader

grep -q '^APP_KEY=$' .env && artisan key:generate --force

echo "==> Building front-end assets"
docker run --rm -v "$PWD":/app -w /app node:20 sh -c "npm ci --no-audit --no-fund && npm run build"

echo "==> Migrating the database"
artisan migrate --force
if [ ! -f storage/app/.demo-seeded ]; then
    artisan db:seed --force
    touch storage/app/.demo-seeded
fi
artisan storage:link || true

# No config:cache: bootstrap/app.php reads TRUSTED_PROXIES with env(), which a cached config would ignore.
artisan route:cache
artisan view:cache
artisan event:cache

echo "==> Starting all services"
docker compose up -d --wait
docker compose restart app queue

# Reset the demo data every night at 03:00, so visitors' edits and test orders don't pile up.
job="0 3 * * * cd $PWD && docker compose exec -T app php artisan migrate:fresh --seed --force >/dev/null 2>&1 # cafeflow-demo-reset"
{ crontab -l 2>/dev/null | grep -v cafeflow-demo-reset || true; echo "$job"; } | crontab -

echo "==> Waiting for HTTPS on https://$site"
for _ in $(seq 1 30); do
    if curl -fsS -o /dev/null "https://$site/up"; then
        echo
        echo "CafeFlow is live:  https://$site"
        echo "Admin:             https://$site/admin"
        echo "API docs:          https://$site/docs/api"
        echo "Demo logins:       admin@cafeflow.demo / manager@cafeflow.demo / customer@cafeflow.demo"
        echo "Password:          $(grep '^CAFEFLOW_DEMO_PASSWORD=' .env | cut -d= -f2-)"
        exit 0
    fi
    sleep 5
done
echo "The app is running, but https://$site is not answering yet."
echo "Check that ports 80 and 443 are open (Oracle security list + server firewall), then: docker compose logs caddy"
exit 1
