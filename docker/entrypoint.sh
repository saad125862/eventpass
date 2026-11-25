#!/bin/sh
set -e

# Render injects PORT (default 10000); fall back to 8080 locally.
# A bare ":port" site address makes Caddy serve plain HTTP (Render terminates TLS).
export SERVER_NAME=":${PORT:-8080}"

# Caddy's admin API isn't needed in production and only adds noise to the logs.
export CADDY_GLOBAL_OPTIONS="${CADDY_GLOBAL_OPTIONS:-admin off}"

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrate on boot. Render only switches traffic to this instance once /health passes,
# so a failing migration never takes the previous release offline.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

# Idempotent demo accounts/events so the live site is never empty.
if [ "${SEED_DEMO:-false}" = "true" ]; then
    php artisan eventpass:seed-demo || echo "demo seeding failed (continuing)"
fi

# Free Render instances have no background workers or cron, so the web container
# supervises its own queue worker and scheduler loops.
if [ "${RUN_WORKER:-false}" = "true" ]; then
    (while true; do php artisan queue:work --sleep=3 --tries=3 --max-time=3600 || true; sleep 2; done) &
fi

if [ "${RUN_SCHEDULER:-false}" = "true" ]; then
    (while true; do php artisan schedule:run --no-interaction || true; sleep 60; done) &
fi

exec "$@"
