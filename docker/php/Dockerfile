# syntax=docker/dockerfile:1.7

FROM php:8.5-cli-alpine

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache git unzip \
    && addgroup -g 1000 app \
    && adduser -D -G app -u 1000 app

WORKDIR /app

ENV COMPOSER_HOME=/tmp/composer \
    COMPOSER_CACHE_DIR=/tmp/composer/cache

USER app

CMD ["php", "-v"]
