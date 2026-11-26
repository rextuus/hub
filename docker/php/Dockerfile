# syntax=docker/dockerfile:1
# Hub – Produktions-Images (compose.prod.yaml). Die Entwicklungsumgebung nutzt weiter das Dockerfile im
# Projektordner (php:8.4.28-apache, compose.yaml).
FROM php:8.5.13-fpm-alpine AS base

RUN apk add --no-cache icu-libs icu-data-full libzip fcgi \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev linux-headers \
 && docker-php-ext-install -j"$(nproc)" intl pdo_mysql zip \
 && pecl install apcu \
 && docker-php-ext-enable apcu \
 && apk del .build-deps

COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer
COPY docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY docker/php/php-fpm.d/zz-app.conf /usr/local/etc/php-fpm.d/zz-app.conf

WORKDIR /srv/app

# Tailwind (nur die Journey, config/packages/symfonycasts_tailwind.yaml): Das Standalone-Binary von v3 gibt es
# nur für glibc, nicht für Alpine – deshalb eine eigene Stufe auf Debian. Version und Prüfsumme wie im Bundle.
FROM debian:13.2-slim AS tailwind
ADD --chmod=755 --checksum=sha256:7d24f7fa191d2193b78cd5f5a42a6093e14409521908529f42d80b11fde1f1d4 \
    https://github.com/tailwindlabs/tailwindcss/releases/download/v3.4.17/tailwindcss-linux-x64 /usr/local/bin/tailwindcss
WORKDIR /src
COPY tailwind.config.js ./
COPY assets/styles/journey.tailwind.css assets/styles/
COPY templates/tool/journey templates/tool/journey
# Dateiname wie beim Bundle (var/tailwind/<eingabe>.built.css); asset-map:compile liest ihn von dort.
RUN tailwindcss -c tailwind.config.js -i assets/styles/journey.tailwind.css -o /out/journey.tailwind.built.css --minify

FROM base AS prod
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
ENV APP_ENV=prod
COPY --chown=www-data:www-data . .
COPY --from=tailwind --chown=www-data:www-data /out/ var/tailwind/
# Die Composer-Auto-Scripts (cache:clear …) laufen einzeln: Beim Bauen gibt es keine Datenbank, und ohne
# serverVersion würde Doctrine sie beim Aufwärmen fragen. Die Dummy-URL gilt nur für diese Befehle und landet
# nicht in .env.local.php (dump-env liest nur die .env-Dateien).
# importmap:install und sass:build laden beim Bauen aus dem Netz (jsDelivr bzw. dart-sass von GitHub).
RUN composer install --no-dev --classmap-authoritative --no-interaction --no-progress --no-scripts \
 && composer dump-env prod \
 && export DATABASE_URL='mysql://build:build@127.0.0.1:3306/build?serverVersion=8.4.13&charset=utf8mb4' \
 && php bin/console assets:install public \
 && php bin/console importmap:install \
 && php bin/console sass:build \
 && php bin/console asset-map:compile \
 && rm -rf var/cache/* var/sass var/tailwind \
 && mkdir -p var/media \
 && chown -R www-data:www-data var
# Medienordner (Volume hub_media_data, siehe compose.prod.yaml). Der Ordner gehört www-data, damit ein neues
# Volume diese Rechte übernimmt.
USER www-data

# Caddy mit den öffentlichen Dateien (kompilierte Assets) aus dem Produktions-Image.
FROM caddy:2.11.9-alpine AS web
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
COPY --from=prod /srv/app/public /srv/app/public
