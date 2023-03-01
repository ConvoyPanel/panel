FROM php:8.2-fpm-alpine

ARG APP_ENV

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql pcntl redis opcache
RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
RUN if [ $APP_ENV = "local" ]; then \
                echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache-cli.ini; \
        fi;

RUN apk add --no-cache bash logrotate supervisor

COPY supervisord.conf /etc/supervisord.conf
COPY laravel-logrotate /etc/logrotate.d/laravel

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]