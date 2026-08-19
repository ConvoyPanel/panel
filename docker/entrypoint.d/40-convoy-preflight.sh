#!/bin/sh
# Convoy preflight. Runs in every container built from this image (web, worker,
# scheduler) before serversideup's 50-laravel-automations.sh.
#
# These scripts are sourced in a subshell by docker-php-serversideup-entrypoint,
# which aborts startup on a non-zero exit. That is deliberate here: every check
# below is a condition that would otherwise surface as an opaque 500 or a queue
# that silently processes nothing, hours after the operator walked away.

script_name="convoy-preflight"

fail() {
    echo "🛑 ERROR ($script_name): $1" >&2
    exit 1
}

##########################################################################
# Storage skeleton
##########################################################################
# storage/ is a volume so logs, sessions and generated files survive a
# `docker compose pull`. A named volume inherits the image's contents on first
# use, but a bind mount to a fresh host directory arrives empty -- and Laravel
# does not create these itself, it just fails to write.
for dir in \
    app/private \
    app/public \
    framework/cache/data \
    framework/sessions \
    framework/testing \
    framework/views \
    logs
do
    target="${APP_BASE_DIR:-/var/www/html}/storage/$dir"
    [ -d "$target" ] && continue

    mkdir -p "$target" 2>/dev/null || fail "could not create $target. The storage volume is not writable by the container user. If you bind-mounted a host directory, chown it to $(id -u):$(id -g) on the host."
done

##########################################################################
# Application key
##########################################################################
# Without this every session cookie and encrypted column is unreadable. It must
# be identical across web, worker and scheduler, which is why it is generated
# once by the installer into .env rather than per-container at boot.
if [ -z "$APP_KEY" ]; then
    fail "APP_KEY is not set. Generate one with 'convoyctl artisan key:generate --show' and put it in your .env, then restart. All three Convoy containers must share the same key."
fi

##########################################################################
# URL
##########################################################################
# Signed URLs (SSO deep links, password resets) and asset paths are all built
# from APP_URL. Left at the framework default, every emailed link points at
# localhost and the operator finds out from a customer.
case "${APP_URL:-}" in
    ""|"http://localhost"|"http://localhost:"*)
        fail "APP_URL is still the default (${APP_URL:-unset}). Set it to the URL customers reach this panel on, including the scheme, or password-reset and SSO links will point at localhost."
        ;;
esac

echo "✅ NOTICE ($script_name): preflight checks passed."
