FROM php:8.1-fpm-alpine3.16

ARG WITH_XDEBUG=false
ARG APP_ENV=local

COPY ./php.ini-* /usr/local/etc/php

RUN if [ $APP_ENV = "production" ]; then \
                cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini; \
                docker-php-ext-install opcache; \
        else \
                cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini; \
        fi

RUN apk add --no-cache bash
RUN docker-php-ext-install pdo_mysql pcntl
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
        && pecl install redis \
        && docker-php-ext-enable redis.so

RUN if [ $WITH_XDEBUG = "true" ]; then \
            pecl install xdebug; \
            docker-php-ext-enable xdebug; \
            echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
            echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
            echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
            echo "xdebug.mode=develop,coverage,debug,profile" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        fi;

RUN echo 'pm.max_children = 100' >> /usr/local/etc/php-fpm.d/zz-docker.conf

CMD ["php-fpm"]

EXPOSE 9000