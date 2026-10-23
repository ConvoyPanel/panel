FROM php:8.2.35-bookworm

ARG PHP_XDEBUG
ARG PHP_XDEBUG_MODE='debug'
ENV PHP_IDE_CONFIG="serverName=convoy"

ENV TZ=UTC
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# update and install in one step, or a rebuild on a machine with an old cached
# package index asks the mirror for versions it no longer carries
RUN apt-get update && \
    apt-get -y install ca-certificates gnupg software-properties-common curl sudo unzip default-mysql-client

RUN sudo mkdir -p /etc/apt/keyrings; \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | sudo gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg; \
    NODE_MAJOR=20; \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list; \
    apt-get update; \
    sudo apt-get install nodejs -y;

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
    apt-get update && \
    apt-get -y install --no-install-recommends $PHPIZE_DEPS && \
    pie install phpredis/phpredis:6.3.0

RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini; \
    echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache-cli.ini;

RUN if [ $PHP_XDEBUG = "true" ]; then \
            install-php-extensions xdebug; \
            echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
            echo "xdebug.mode=$PHP_XDEBUG_MODE" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
            echo "xdebug.idekey=convoy" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
            echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        fi;

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
    php composer-setup.php; \
    php -r "unlink('composer-setup.php');"; \
    sudo mv composer.phar /usr/local/bin/composer;

RUN echo "alias art='php artisan'" >> ~/.bashrc

WORKDIR /var/www
