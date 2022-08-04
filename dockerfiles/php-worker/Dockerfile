FROM php:8.1-fpm-alpine3.16

RUN apk add --no-cache bash
RUN docker-php-ext-install pdo_mysql
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
        && pecl install redis \
        && docker-php-ext-enable redis.so

CMD ["php", "artisan", "queue:work"]