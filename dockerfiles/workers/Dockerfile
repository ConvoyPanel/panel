FROM php:8.2-fpm-alpine

RUN apk add --no-cache bash
RUN docker-php-ext-install pdo_mysql pcntl
RUN apk add --no-cache supervisor pcre-dev $PHPIZE_DEPS \
        && pecl install redis \
        && docker-php-ext-enable redis.so

COPY supervisord.conf /etc/supervisord.conf

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]