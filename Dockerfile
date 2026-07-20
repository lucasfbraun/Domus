# syntax=docker/dockerfile:1
# Production image for Dokku (not Sail).
# Serves on :8080 — Dokku nginx terminates TLS and proxies here.

# -----------------------------------------------------------------------------
# 1) PHP dependencies
# -----------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --ignore-platform-reqs

COPY . .

RUN composer dump-autoload \
    --optimize \
    --classmap-authoritative \
    --no-dev \
    --no-interaction

# -----------------------------------------------------------------------------
# 2) Frontend assets (Wayfinder runs `php artisan` during `vite build`)
# -----------------------------------------------------------------------------
FROM serversideup/php:8.5-cli-alpine AS assets

USER root

WORKDIR /var/www/html

RUN apk add --no-cache nodejs npm

COPY --from=vendor --chown=www-data:www-data /app /var/www/html

# Minimal env so Wayfinder/Vite can boot Artisan without DB/Redis.
RUN cp .env.example .env \
    && php artisan key:generate --force --no-interaction \
    && mkdir -p database \
    && touch database/database.sqlite \
    && php artisan package:discover --ansi \
    && npm ci \
    && npm run build \
    && rm -f .env database/database.sqlite \
    && rm -rf node_modules

# -----------------------------------------------------------------------------
# 3) Runtime — Nginx + PHP-FPM
# -----------------------------------------------------------------------------
FROM serversideup/php:8.5-fpm-nginx-alpine

USER root

# Spatie Media Library image-optimizer binaries (+ svgo for SVG).
# bash is required: Dokku runs app.json predeploy/release via `/bin/bash`.
# Do not `apk del` here — this image pins nginx from a custom repo; deleting
# packages makes apk revalidate world tags and fail the build.
RUN apk add --no-cache \
        bash \
        jpegoptim \
        optipng \
        pngquant \
        gifsicle \
        libwebp-tools \
        nodejs \
        npm \
    && npm install -g svgo \
    && npm cache clean --force

# Extensions beyond the image defaults (GD/WebP, queues, DB, Redis).
RUN install-php-extensions \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        redis \
        zip

WORKDIR /var/www/html

COPY --from=assets --chown=www-data:www-data /var/www/html /var/www/html

RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

# Dokku terminates TLS; app listens on 8080 (serversideup non-root default).
ENV SSL_MODE=off \
    AUTORUN_ENABLED=false \
    PHP_OPCACHE_ENABLE=1 \
    PHP_MEMORY_LIMIT=512M \
    PHP_POST_MAX_SIZE=100M \
    PHP_UPLOAD_MAX_FILESIZE=100M

# Clear image ENTRYPOINT so Dokku Procfile commands run as-is.
ENTRYPOINT []

EXPOSE 8080

HEALTHCHECK --interval=5s --timeout=3s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:8080/up || exit 1

CMD ["/init"]
