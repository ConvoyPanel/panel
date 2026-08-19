# syntax=docker/dockerfile:1.9

# Convoy ships as a single image. The web, queue-worker and scheduler containers
# in compose.yml are all *this* image with different commands -- there is no
# second "compact" image, because the difference between a bundled and an
# external database is which host DB_HOST points at, not which artifact you run.
#
# The base images are serversideup/php (GPL-3.0), pinned by digest so a rebuild
# is reproducible and so we always know exactly which upstream version we are
# redistributing. See NOTICE.md. We add files alongside theirs (docker/entrypoint.d)
# rather than editing their scripts, which keeps our layer clearly separate from
# a copyleft one.

# Both stages that only produce architecture-independent output (PHP sources and
# compiled JS) are pinned to BUILDPLATFORM: running them once natively instead of
# under emulation is the difference between a 4-minute and a 40-minute arm64 build.
ARG PHP_CLI_IMAGE=serversideup/php:8.4-cli-alpine-v4.5.1@sha256:968edae34d871b593e77e629686d3f664c9a017e6946af19babbe5a5382c2331
ARG PHP_RUNTIME_IMAGE=serversideup/php:8.4-frankenphp-alpine-v4.5.1@sha256:2e41d837255dae28b5c3ea44a2f0817bab3adc903c9e35e795326608198fdfd7

# Alpine's own repositories carry Node 24; CI and ddev both build the frontend
# on Node 22, and the build that ships should be the one that is tested.
ARG NODE_IMAGE=node:22-alpine

# gmp is load-bearing (Support/Network.php and the address-availability maths run
# on every relevant request) and is NOT in the serversideup default set, which is
# opcache/pcntl/pdo_mysql/pdo_pgsql/redis/zip. The rest mirrors the extension list
# in .github/workflows/tests.yml so the image and CI agree on what PHP looks like.
ARG EXTRA_PHP_EXTENSIONS="gmp bcmath intl pgsql"

##########################################################################
# Stage 0 -- Node toolchain
##########################################################################
# Only ever used as a source for COPY. Declaring it as a stage is what lets the
# image reference stay an ARG: `COPY --from=${ARG}` is resolved before build
# args are substituted and fails to parse.
FROM --platform=${BUILDPLATFORM} ${NODE_IMAGE} AS node

##########################################################################
# Stage 1 -- Composer dependencies
##########################################################################
FROM --platform=${BUILDPLATFORM} ${PHP_CLI_IMAGE} AS vendor

ARG EXTRA_PHP_EXTENSIONS
USER root
RUN install-php-extensions ${EXTRA_PHP_EXTENSIONS}
USER www-data

WORKDIR /var/www/html

# Manifests first, so a source-only change does not re-resolve every package.
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

COPY --chown=www-data:www-data . .

# `composer dump-autoload` fires post-autoload-dump -> `artisan package:discover`,
# which boots the framework. It needs an .env to boot but never a database (the
# release workflow proves this: it builds with no Postgres service). The file is
# removed immediately afterwards so no build-time config survives into the image.
RUN cp .env.example .env \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && rm -f .env

##########################################################################
# Stage 2 -- Frontend assets
##########################################################################
# This stage needs PHP as well as Node: `npm run build` runs a `prebuild` hook
# (`artisan typescript:transform` + `artisan wayfinder:generate`), and both of
# those outputs are gitignored, so they cannot be copied in from the context.
FROM --platform=${BUILDPLATFORM} vendor AS assets

# Node is copied in rather than installed from Alpine's repositories so the
# version matches CI. Both images are musl-based, so the binary is compatible;
# it needs libstdc++, which the CLI variant does not ship by default.
USER root
RUN apk add --no-cache libstdc++
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
USER www-data

WORKDIR /var/www/html

# The prebuild artisan commands boot the app, so they need an APP_KEY present.
# It is a throwaway: the real key comes from the host .env at runtime.
RUN cp .env.example .env \
    && php artisan key:generate --ansi \
    && npm ci --no-audit --no-fund \
    && npm run build \
    && rm -rf node_modules .env

##########################################################################
# Stage 3 -- Runtime
##########################################################################
FROM ${PHP_RUNTIME_IMAGE} AS runtime

ARG EXTRA_PHP_EXTENSIONS
USER root
RUN install-php-extensions ${EXTRA_PHP_EXTENSIONS}

# Our own entrypoint scripts. Numbered below 50 so they run before serversideup's
# 50-laravel-automations.sh -- the storage skeleton has to exist before
# `storage:link` and the first log write, and a missing APP_KEY should be a clear
# error rather than a 500 on the login page.
COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/
USER www-data

ENV APP_ENV=production \
    APP_DEBUG=false \
    # There is no log file to tail in a container. Laravel's default `stack`
    # channel writes to storage/logs; stderr puts everything in `docker logs`
    # alongside Caddy's own output. Raise to debug when chasing something.
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    # Laravel's health route (bootstrap/app.php, health: '/up'). The image default
    # is /healthcheck, which Caddy answers itself without ever touching PHP -- a
    # container that reports healthy while the app is broken is worse than none.
    HEALTHCHECK_PATH=/up \
    # Off by default upstream; an unconfigured opcache is the single most common
    # reason a containerised Laravel app is inexplicably slow.
    PHP_OPCACHE_ENABLE=1 \
    # The application code in this image is immutable, so there is nothing to
    # revalidate. Compiled Blade views are written once at boot, before the
    # server starts accepting requests.
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000 \
    PHP_MEMORY_LIMIT=512M \
    # Automations are opted into per-service in compose.yml: exactly one container
    # may run migrations, and the worker/scheduler must not race it.
    AUTORUN_ENABLED=false

WORKDIR /var/www/html

COPY --from=assets --chown=www-data:www-data /var/www/html /var/www/html

# Stamped the same way the release workflow stamps a tarball build. The pattern
# matches whatever version is currently committed rather than a literal
# 'canary', so the stamp cannot silently no-op when that value is changed.
ARG CONVOY_VERSION=canary
RUN sed -i "s/'version' => '[^']*',/'version' => '${CONVOY_VERSION}',/" config/app.php \
    && grep -q "'version' => '${CONVOY_VERSION}'," config/app.php

LABEL org.opencontainers.image.title="Convoy" \
      org.opencontainers.image.description="KVM server management panel for hosting businesses." \
      org.opencontainers.image.url="https://convoypanel.com" \
      org.opencontainers.image.source="https://github.com/ConvoyPanel/panel" \
      org.opencontainers.image.documentation="https://docs.convoypanel.com" \
      org.opencontainers.image.vendor="Performave" \
      org.opencontainers.image.version="${CONVOY_VERSION}"
