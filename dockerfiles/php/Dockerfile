FROM php:8.2.35-fpm-alpine

ARG PHP_XDEBUG
ARG PHP_XDEBUG_MODE='debug'
ARG APP_ENV
ENV PHP_IDE_CONFIG="serverName=convoy"
ENV PHP_MAX_CHILDREN=100

# Pinned, not `latest`: Docker re-downloads a URL on every build, so each new
# release of the installer threw away the cached layers below and reinstalled
# every extension, which is how a rebuild could fail on a day nothing changed.
ADD https://github.com/mlocati/docker-php-extension-installer/releases/download/2.12.3/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql pcntl opcache gmp

# redis is the one extension PHP doesn't ship with. It comes from Packagist and
# GitHub through PIE rather than from PECL, which the PHP project has deprecated
# and which fails for hours at a time.
RUN --mount=type=bind,from=ghcr.io/php/pie:1.5.2-bin,source=/pie,target=/usr/local/bin/pie \
    apk add --no-cache --virtual .pie-deps $PHPIZE_DEPS unzip && \
    pie install phpredis/phpredis:6.3.0 && \
    apk del .pie-deps

RUN if [ $APP_ENV = "local" ]; then \
                echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache-cli.ini; \
        fi;

RUN if [ $PHP_XDEBUG = "true" ]; then \
            install-php-extensions xdebug; \
            echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
            echo "xdebug.mode=$PHP_XDEBUG_MODE" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
            echo "xdebug.idekey=convoy" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
            echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        fi;

RUN echo "pm.max_children = $PHP_MAX_CHILDREN" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# The official image ships no php.ini, so PHP keeps its compiled-in default of
# announcing its exact version in an X-Powered-By header on every response.
RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/zz-convoy.ini

COPY --chmod=755 entrypoint.sh /usr/local/bin/convoy-entrypoint

WORKDIR /var/www

ENTRYPOINT ["convoy-entrypoint"]
CMD ["php-fpm"]
