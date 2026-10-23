#!/bin/sh
# PHP-FPM serves the panel as www-data. Files left behind by root -- artisan run
# from the workspace container writes the log and caches as root -- would stop
# it writing them, so ownership is put right on every start.
set -e
for dir in /var/www/storage /var/www/bootstrap/cache; do
    [ -d "$dir" ] && chown -R www-data:www-data "$dir"
done
exec docker-php-entrypoint "$@"
