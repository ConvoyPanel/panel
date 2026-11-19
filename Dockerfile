# syntax=docker/dockerfile:1.9

# Convoy ships as a single image. The web, queue-worker and scheduler containers
# in compose.yml are all *this* image with different commands -- there is no
# second "compact" image, because the difference between a bundled and an
# external database is which host DB_HOST points at, not which artifact you run.
#
# The base images are serversideup/php (GPL-3.0), pinned by digest so a rebuild
# is reproducible and so we always know exactly which upstream version we are
# redistributing. We add files alongside theirs (docker/entrypoint.d) rather
# than editing their scripts, which keeps our layer clearly separate from a
# copyleft one.

# Both stages that only produce architecture-independent output (PHP sources and
# compiled JS) are pinned to BUILDPLATFORM: running them once natively instead of
# under emulation is the difference between a 4-minute and a 40-minute arm64 build.
ARG PHP_CLI_IMAGE=serversideup/php:8.4-cli-alpine-v5.1.5@sha256:fcc1035b180c950797cb55883e81fb91fafd0bd00da05ae73651b287911a1fd1
ARG PHP_RUNTIME_IMAGE=serversideup/php:8.4-frankenphp-alpine-v5.1.5@sha256:a774355e34efde1ee7e17ae0602b608b0a1bd726800562307d04249cfef07cae

# The frontend is built on Node 22 in CI and ddev, and the build that ships
# should be the one that is tested.
ARG NODE_IMAGE=node:22-alpine

# gmp is load-bearing (the address-availability maths in app/Helpers/Network.php
# runs on every relevant request) and is NOT in the serversideup default set,
# which is opcache/pcntl/pdo_mysql/pdo_pgsql/redis/zip.
ARG EXTRA_PHP_EXTENSIONS="gmp bcmath intl pgsql"

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
# which boots the framework. It needs an .env to boot but never a database. The
# file is removed immediately afterwards so no build-time config survives into
# the image.
RUN cp .env.example .env \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && rm -f .env

##########################################################################
# Stage 2 -- Frontend assets
##########################################################################
# `npm run build` is a plain `vite build`: no artisan prebuild step, so unlike
# the PHP stage this one needs nothing but Node and the frontend sources.
FROM --platform=${BUILDPLATFORM} ${NODE_IMAGE} AS assets

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.ts tsconfig.json tsconfig.node.json tailwind.config.js postcss.config.js ./
COPY resources/ resources/
COPY public/ public/
RUN npm run build

##########################################################################
# Stage 3 -- Runtime
##########################################################################
FROM ${PHP_RUNTIME_IMAGE} AS runtime

ARG EXTRA_PHP_EXTENSIONS
USER root
RUN install-php-extensions ${EXTRA_PHP_EXTENSIONS}

# The base image sends a year-long HSTS header with includeSubDomains on HTTPS,
# which v4 never did. Browsers then force HTTPS on every port of the panel's
# hostname, so a Coterm there without TLS can't open a console. max-age=0 makes
# browsers that saw the old header forget it.
RUN sed -i 's/Strict-Transport-Security "[^"]*"/Strict-Transport-Security "max-age=0"/' /etc/frankenphp/Caddyfile \
    && grep -q 'Strict-Transport-Security "max-age=0"' /etc/frankenphp/Caddyfile \
    && ! grep -rn 'Strict-Transport-Security' /etc/frankenphp | grep -v '"max-age=0"'

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
    # Caddy writes JSON when there's no terminal, which in a container is
    # always. People read `convoy logs web`, so keep its lines plain text.
    CADDY_LOG_FORMAT=console \
    # The base image defaults to trusting Cloudflare and every private range to
    # name the client, and hands that address to PHP as REMOTE_ADDR. Visitors
    # over IPv6, a LAN or a VPN arrive from a private address, so they could
    # pick their own IP with a header and skip the login rate limit. Proxies
    # are trusted in Laravel instead, through TRUSTED_PROXIES.
    TRUSTED_PROXY=off \
    # routes/base.php answers /up after checking the database and Redis. The
    # image default is /healthcheck, which Caddy answers itself without ever
    # touching PHP -- a container that reports healthy while the app is broken
    # is worse than none.
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

COPY --from=vendor --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=assets --chown=www-data:www-data /build/public/build /var/www/html/public/build

# Stamped the same way the release workflow stamps a tarball build. The pattern
# matches whatever version is currently committed rather than a literal
# 'canary', so the stamp cannot silently no-op when that value is changed.
# docker/convoy is stamped too: `convoy upgrade` installs the host's convoy
# command from the image it is upgrading to, so the command always matches the
# panel it runs.
ARG CONVOY_VERSION=canary
RUN sed -i "s/'version' => '[^']*',/'version' => '${CONVOY_VERSION}',/" config/app.php \
    && grep -q "'version' => '${CONVOY_VERSION}'," config/app.php \
    && sed -i "s/^CONVOY_CLI_VERSION=\"[^\"]*\"/CONVOY_CLI_VERSION=\"${CONVOY_VERSION}\"/" docker/convoy \
    && grep -q "^CONVOY_CLI_VERSION=\"${CONVOY_VERSION}\"" docker/convoy

LABEL org.opencontainers.image.title="Convoy" \
      org.opencontainers.image.description="KVM server management panel for hosting businesses." \
      org.opencontainers.image.url="https://convoypanel.com" \
      org.opencontainers.image.source="https://github.com/ConvoyPanel/panel" \
      org.opencontainers.image.documentation="https://docs.convoypanel.com" \
      org.opencontainers.image.vendor="Performave" \
      org.opencontainers.image.version="${CONVOY_VERSION}"
