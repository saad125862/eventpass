#!/bin/sh
set -e

# Render injects PORT (default 10000); fall back to 8080 locally.
# A bare ":port" site address makes Caddy serve plain HTTP (Render terminates TLS).
export SERVER_NAME=":${PORT:-8080}"

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Phase 1 convenience: migrate on boot. Phase 10 moves this into a dedicated deploy step.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
