FROM dunglas/frankenphp:php8.4-trixie

ARG PHP_XDEBUG
ARG PHP_XDEBUG_MODE='debug'
ARG APP_ENV
ENV PHP_IDE_CONFIG="serverName=convoy"

RUN apt-get update
RUN apt-get -y install ca-certificates gnupg curl sudo unzip default-mysql-client
RUN install-php-extensions pdo_mysql pcntl redis gmp @composer

RUN if [ $APP_ENV = "production" ]; then \
                install-php-extensions opcache; \
                echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache-cli.ini; \
        fi;

RUN if [ $PHP_XDEBUG = "true" ]; then \
    install-php-extensions xdebug; \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    echo "xdebug.mode=$PHP_XDEBUG_MODE" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#    echo "xdebug.idekey=convoy" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
fi;

COPY ./Caddyfile /etc/frankenphp/Caddyfile
COPY ./entrypoint.sh /usr/local/bin/entrypoint.sh

RUN sudo mkdir -p /etc/apt/keyrings; \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | sudo gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg; \
    NODE_MAJOR=22; \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list; \
    apt-get update; \
    sudo apt-get install nodejs -y;

RUN echo "alias art='php artisan'" >> ~/.bashrc

WORKDIR /app

ENTRYPOINT ["entrypoint.sh"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]