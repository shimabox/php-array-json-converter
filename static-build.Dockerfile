# syntax=docker/dockerfile:1.7

FROM composer:2 AS app

WORKDIR /app

COPY composer.json composer.lock ./
COPY src ./src
COPY public ./public
COPY Caddyfile ./Caddyfile

RUN composer install \
    --ignore-platform-reqs \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --classmap-authoritative

FROM --platform=linux/amd64 dunglas/frankenphp:static-builder-gnu

WORKDIR /go/src/app/dist/app

COPY --from=app /app ./

WORKDIR /go/src/app

RUN PHP_EXTENSIONS=tokenizer \
    PHP_EXTENSION_LIBS=watcher \
    SPC_CMD_VAR_FRANKENPHP_XCADDY_MODULES=" " \
    EMBED=dist/app/ \
    ./build-static.sh
