# syntax=docker/dockerfile:1

# ---- 1. PHP dependencies (no dev packages) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction --ignore-platform-reqs

# ---- 2. Frontend assets (Vite) ----
FROM node:26-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY vite.config.js ./
COPY public ./public
RUN npm run build

# ---- 2b. Dev image (used by docker-compose only; never the default build target) ----
FROM dunglas/frankenphp:1-php8.4-alpine AS dev
RUN install-php-extensions pdo_pgsql opcache bcmath pcntl gd zip pcov \
    && apk add --no-cache git unzip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
EXPOSE 8000
CMD ["frankenphp", "php-server", "--root", "public", "--listen", ":8000"]

# ---- 3. Runtime: FrankenPHP (Caddy + PHP in one binary, no nginx/php-fpm to configure) ----
FROM dunglas/frankenphp:1-php8.4-alpine AS runtime

RUN install-php-extensions pdo_pgsql opcache bcmath pcntl \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    # We listen on an unprivileged port (>1024), so drop the binary's file capability.
    # Without this, platforms that don't grant NET_BIND_SERVICE (e.g. Render) refuse to exec it.
    && setcap -r /usr/local/bin/frankenphp

WORKDIR /app
COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Empty by default so Render's RENDER_GIT_COMMIT can supply the version at runtime.
ARG APP_VERSION=""
ENV APP_NAME=EventPass \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    APP_VERSION=${APP_VERSION}

# Run as a non-root user.
RUN addgroup -S app && adduser -S -G app app \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R app:app /app/storage /app/bootstrap/cache /data /config
USER app

EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD wget -qO- "http://127.0.0.1:${PORT:-8080}/up" >/dev/null || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
