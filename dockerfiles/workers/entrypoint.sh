#!/bin/sh
# Horizon and the scheduler run as www-data (see supervisord.conf), the same
# user PHP-FPM serves the panel as. Files left behind by root -- an earlier
# release of this image, or artisan run from the workspace container -- would
# stop them writing logs and cache, so ownership is put right on every start.
set -e
for dir in /var/www/storage /var/www/bootstrap/cache; do
    [ -d "$dir" ] && chown -R www-data:www-data "$dir"
done
exec /usr/bin/supervisord -c /etc/supervisord.conf
