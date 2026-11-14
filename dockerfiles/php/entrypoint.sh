#!/bin/sh
# PHP-FPM serves the panel as www-data. Files left behind by root -- artisan run
# from the workspace container writes the log and caches as root -- would stop
# it writing them, so ownership is put right on every start.
set -e
for dir in /var/www/storage /var/www/bootstrap/cache; do
    [ -d "$dir" ] && chown -R www-data:www-data "$dir"
done
# Earlier releases shipped the bundle size report as public/stats.html, and an
# update extracts the new archive over the old one, which deletes nothing, so
# Caddy kept serving it to anyone.
rm -f /var/www/public/stats.html
exec docker-php-entrypoint "$@"
