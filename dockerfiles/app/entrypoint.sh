#!/bin/bash
set -e

cd /app

# Install/update composer deps if lock file changed since last boot
LOCK_HASH_FILE="/tmp/.composer-lock-hash"
CURRENT_HASH=$(md5sum composer.lock 2>/dev/null | cut -d' ' -f1 || echo "none")
PREV_HASH=$(cat "$LOCK_HASH_FILE" 2>/dev/null || echo "")

if [ "$CURRENT_HASH" != "$PREV_HASH" ]; then
    echo "[entrypoint] composer.lock changed — running composer install..."
    composer install --no-interaction --optimize-autoloader 2>&1
    echo "$CURRENT_HASH" > "$LOCK_HASH_FILE"
else
    echo "[entrypoint] composer deps up to date, skipping install."
fi

# Clear Laravel caches so we never boot with stale config
echo "[entrypoint] Clearing Laravel caches..."
php artisan optimize:clear 2>&1 || true

# Hand off to FrankenPHP
exec docker-php-entrypoint "$@"
