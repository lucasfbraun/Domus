# syntax=docker/dockerfile:1
# Production image for Dokku (not Sail).
# FrankenPHP on :8080 — Dokku nginx terminates TLS and proxies here.
# Avoids serversideup/s6, which breaks under Dokku ("can only run as pid 1").

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
FROM dunglas/frankenphp:php8.5-alpine AS assets

WORKDIR /app

RUN apk add --no-cache nodejs npm

COPY --from=vendor /app /app

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
# 3) Runtime — FrankenPHP (Caddy + PHP)
# -----------------------------------------------------------------------------
FROM dunglas/frankenphp:php8.5-alpine

WORKDIR /app

# bash: Dokku app.json predeploy/release. Optimizers: Spatie Media Library.
RUN apk add --no-cache \
        bash \
        curl \
        jpegoptim \
        optipng \
        pngquant \
        gifsicle \
        libwebp-tools \
        nodejs \
        npm \
    && npm install -g svgo \
    && npm cache clean --force

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

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

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf '%s\n' \
        'upload_max_filesize = 100M' \
        'post_max_size = 100M' \
        'memory_limit = 512M' \
        > "$PHP_INI_DIR/conf.d/99-app.ini"

COPY --from=assets /app /app

RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

# HTTP only on 8080 (TLS is terminated by Dokku).
ENV SERVER_NAME=:8080

EXPOSE 8080

HEALTHCHECK --interval=5s --timeout=3s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:8080/up || exit 1

CMD ["frankenphp", "php-server", "--listen", ":8080", "--root", "public/"]
