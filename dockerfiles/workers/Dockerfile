FROM php:8.2.35-fpm-alpine

ARG APP_ENV

# Pinned, not `latest`: a new release of the installer image threw away the
# cached layers below and reinstalled every extension on the next build.
COPY --from=mlocati/php-extension-installer:2.12.3 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql pcntl opcache

# redis is the one extension PHP doesn't ship with. It comes from Packagist and
# GitHub through PIE rather than from PECL, which the PHP project has deprecated
# and which fails for hours at a time.
RUN --mount=type=bind,from=ghcr.io/php/pie:1.5.2-bin,source=/pie,target=/usr/local/bin/pie \
    apk add --no-cache --virtual .pie-deps $PHPIZE_DEPS unzip && \
    pie install phpredis/phpredis:6.3.0 && \
    apk del .pie-deps

RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
RUN if [ $APP_ENV = "local" ]; then \
                echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache-cli.ini; \
        fi;

RUN apk add --no-cache bash logrotate supervisor

COPY supervisord.conf /etc/supervisord.conf
COPY laravel-logrotate /etc/logrotate.d/laravel
COPY --chmod=755 entrypoint.sh /usr/local/bin/convoy-entrypoint

CMD ["convoy-entrypoint"]
