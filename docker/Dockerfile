# syntax=docker/dockerfile:1.7

ARG PHP_VERSION=8.4
ARG FRANKENPHP_VERSION=1

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}-bookworm AS app_base

ARG UID=1000
ARG GID=1000

RUN setcap cap_net_bind_service=+ep /usr/local/bin/frankenphp

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
        pdo_pgsql \
        intl \
        opcache \
        zip \
        @composer

RUN groupadd --force --gid ${GID} app && \
    useradd --no-user-group --gid ${GID} --uid ${UID} --shell /bin/bash --create-home app && \
    mkdir -p /data/caddy /config/caddy /app && \
    chown -R ${UID}:${GID} /data /config /app

WORKDIR /app
USER app

ENV PATH="/app/bin:/app/vendor/bin:${PATH}"
ENV COMPOSER_HOME=/home/app/.composer

FROM app_base AS dev

ENV APP_ENV=dev
ENV XDEBUG_MODE=off

USER root
RUN install-php-extensions xdebug
USER app

COPY --chown=${UID}:${GID} Caddyfile /etc/caddy/Caddyfile
COPY --chown=${UID}:${GID} conf.d/ /usr/local/etc/php/conf.d/
