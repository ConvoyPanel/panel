FROM php:8.1-fpm-alpine3.16

ARG WITH_XDEBUG=false

COPY ./php.ini /usr/local/etc/php/php.ini

RUN apk add --no-cache bash
RUN docker-php-ext-install pdo_mysql
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

WORKDIR /var/www

CMD ["php-fpm"]

EXPOSE 9000