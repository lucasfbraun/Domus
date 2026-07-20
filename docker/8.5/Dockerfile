FROM node:24-alpine AS node

FROM php:8.5-cli-alpine

LABEL maintainer="Laravel"

ARG WWWGROUP=1337

WORKDIR /var/www/html

ENV TZ=UTC
ENV LANG=C.UTF-8
ENV SUPERVISOR_PHP_COMMAND="/usr/local/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"

# System packages + Spatie image-optimizer binaries (webp conversions).
RUN apk add --no-cache \
        curl \
        git \
        shadow \
        su-exec \
        libcap \
        jpegoptim \
        optipng \
        pngquant \
        gifsicle \
        libwebp-tools \
        sqlite \
        supervisor \
        unzip \
        zip \
        tzdata \
    && cp /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone

# PHP extensions required by Laravel + Media Library (GD/WebP).
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
        pdo_sqlite \
        redis \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js (Vite) from the official Alpine image.
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && npm install -g svgo \
    && npm cache clean --force

RUN setcap "cap_net_bind_service=+ep" /usr/local/bin/php

RUN groupadd --force -g $WWWGROUP sail \
    && useradd -ms /bin/sh --no-user-group -g $WWWGROUP -u 1337 sail \
    && git config --global --add safe.directory /var/www/html \
    && mkdir -p /var/log/supervisor /etc/supervisor/conf.d

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /usr/local/etc/php/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

EXPOSE 80/tcp

ENTRYPOINT ["start-container"]
