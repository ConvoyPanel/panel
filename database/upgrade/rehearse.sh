#!/usr/bin/env bash
#
# Upgrade rehearsal: runs the whole v4 -> 5.x database path end to end on
# seeded, prod-shaped data, the way `convoy import-v4` runs it on a real install.
#
#   1. Checks out v4 (V4_REF, default origin/4.x) into .upgrade-v4/ and starts a
#      throwaway MySQL 8.4 on ddev's network.
#   2. Runs v4's own migrations and seeds it with v4's own models
#      (fixtures/V4RehearsalSeeder.php).
#   3. pgloader (v4-to-5.load) copies it into a scratch Postgres database, and
#      every table's row count is compared with MySQL's.
#   4. 5.x's `artisan migrate` runs on the converted data, with the v4 APP_KEY.
#   5. `artisan import:verify` boots 5.x on the result: decryption, every model,
#      v4 API tokens.
#   6. The schema is diffed against a fresh 5.x install at the same migration.
#
# Nothing touches the dev database. Needs docker and the ddev project running.
# Run it after adding a migration:      bash database/upgrade/rehearse.sh
# Keep the scratch databases to inspect: KEEP=1 bash database/upgrade/rehearse.sh
#
# Adapted from the new panel's database/cutover/rehearse.sh.
#
set -euo pipefail

PROJECT="${DDEV_PROJECT:-convoy5x}"
NETWORK="${DDEV_NETWORK:-ddev-${PROJECT}_default}"
PG_CONTAINER="${PG_CONTAINER:-ddev-${PROJECT}-db}"
PG_USER="${PG_USER:-db}"
PG_PASS="${PG_PASS:-db}"
V4_REF="${V4_REF:-origin/4.x}"
# 3.6.10, pinned. The Docker Hub image (dimitri/pgloader) is 3.6.7, too old to
# talk to MySQL 8. amd64 only: SBCL segfaults under qemu-user, so on an arm64
# Linux host point this at a native build.
PGLOADER_IMAGE="${PGLOADER_IMAGE:-ghcr.io/dimitri/pgloader@sha256:a1d4a78e78a64e46cd3fc7dfc57d24eb91ffb1a5520f2b1f55631815e3658d6e}"
MYSQL_IMAGE="${MYSQL_IMAGE:-mysql:8.4}"
MYSQL_CONTAINER="upgrade-rehearsal-mysql"
MYSQL_DB="convoy_v4"
MYSQL_PASS="root"
PG_TARGET="upgrade_target"
PG_FRESH="upgrade_fresh"

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
UPGRADE="$REPO_ROOT/database/upgrade"
V4_DIR="$REPO_ROOT/.upgrade-v4"
V4_IN_WEB="/var/www/html/.upgrade-v4"
WORK="$(mktemp -d -t upgrade.XXXXXX)"
MYSQL_ENV="DB_CONNECTION=mysql DB_HOST=$MYSQL_CONTAINER DB_PORT=3306 DB_DATABASE=$MYSQL_DB DB_USERNAME=root DB_PASSWORD=$MYSQL_PASS"

pg()     { docker exec -i "$PG_CONTAINER" env PGPASSWORD="$PG_PASS" psql -U "$PG_USER" -v ON_ERROR_STOP=1 "$@"; }
mysqlc() { docker exec "$MYSQL_CONTAINER" mysql -uroot -p"$MYSQL_PASS" -D "$MYSQL_DB" -N "$@" 2>/dev/null; }
# The v4 checkout carries its own .ddev, so ddev warns about a nested project.
v4()     { ddev exec -d "$V4_IN_WEB" "$@" 2>&1 | { grep -v "nested project\|To use the nested" || true; }; }
app()    { ddev exec "$@"; }
step()   { echo ""; echo "=== $* ==="; }

cleanup() {
    echo ""
    if [ "${KEEP:-0}" = "1" ]; then
        echo "KEEP=1: left $MYSQL_CONTAINER, $PG_TARGET, ${PG_TARGET}_pristine and $PG_FRESH in place."
    else
        docker rm -f "$MYSQL_CONTAINER" >/dev/null 2>&1 || true
        for db in "$PG_TARGET" "${PG_TARGET}_pristine" "$PG_FRESH"; do
            pg -d postgres -c "DROP DATABASE IF EXISTS $db WITH (FORCE);" >/dev/null 2>&1 || true
        done
    fi
    rm -rf "$WORK"
}
trap cleanup EXIT

step "1. v4 checkout ($V4_REF) + throwaway MySQL 8.4"
if [ ! -d "$V4_DIR" ]; then
    git -C "$REPO_ROOT" worktree add --detach "$V4_DIR" "$V4_REF" >/dev/null
else
    git -C "$V4_DIR" checkout -q --detach "$V4_REF"
fi
cp "$UPGRADE/fixtures/V4RehearsalSeeder.php" "$V4_DIR/database/seeders/"
[ -f "$V4_DIR/.env" ] || cp "$V4_DIR/.env.example" "$V4_DIR/.env"
[ -d "$V4_DIR/vendor" ] || v4 composer install -n -q --no-scripts
v4 composer dump-autoload -q
grep -q '^APP_KEY=base64' "$V4_DIR/.env" || v4 php artisan key:generate -n -q
V4_APP_KEY="$(grep '^APP_KEY=' "$V4_DIR/.env" | cut -d= -f2-)"

# pgloader's MySQL driver cannot finish a caching_sha2_password handshake, so
# the server has to offer mysql_native_password, which MySQL 8.4 no longer
# loads by default. `convoy import-v4` starts its MySQL the same way.
docker rm -f "$MYSQL_CONTAINER" >/dev/null 2>&1 || true
docker run -d --name "$MYSQL_CONTAINER" --network "$NETWORK" \
    -e MYSQL_ROOT_PASSWORD="$MYSQL_PASS" -e MYSQL_DATABASE="$MYSQL_DB" \
    "$MYSQL_IMAGE" --mysql-native-password=ON --authentication-policy=mysql_native_password >/dev/null
for _ in $(seq 1 120); do mysqlc -e "SELECT 1;" >/dev/null && break; sleep 1; done
mysqlc -e "SELECT 1;" >/dev/null || { docker logs --tail 20 "$MYSQL_CONTAINER"; echo "MySQL did not become ready." >&2; exit 1; }

step "2. v4 migrate + seed"
v4 "$MYSQL_ENV php artisan migrate --force -q && $MYSQL_ENV php artisan db:seed --class=V4RehearsalSeeder --force -q"
echo "servers=$(mysqlc -e 'SELECT COUNT(*) FROM servers') addresses=$(mysqlc -e 'SELECT COUNT(*) FROM ip_addresses') tokens=$(mysqlc -e 'SELECT COUNT(*) FROM personal_access_tokens')"

step "3. pgloader"
pg -d postgres -q -c "DROP DATABASE IF EXISTS $PG_TARGET WITH (FORCE);" -c "CREATE DATABASE $PG_TARGET OWNER $PG_USER;" 2>&1 | grep -v NOTICE || true
sed -e "s|\${MYSQL_URL}|mysql://root:$MYSQL_PASS@$MYSQL_CONTAINER/$MYSQL_DB|" \
    -e "s|\${PG_URL}|postgresql://$PG_USER:$PG_PASS@db/$PG_TARGET|" \
    -e "s|\${MYSQL_DB}|$MYSQL_DB|" \
    "$UPGRADE/v4-to-5.load" > "$WORK/upgrade.load"
docker run --rm --network "$NETWORK" -v "$WORK/upgrade.load:/load/upgrade.load:ro" \
    "$PGLOADER_IMAGE" pgloader /load/upgrade.load > "$WORK/pgloader.log" 2>&1 || true
# pgloader exits 0 even when it never connected, so read the log instead.
if grep -qE " ERROR |FATAL" "$WORK/pgloader.log" || ! grep -q "Total import time" "$WORK/pgloader.log"; then
    grep -E " ERROR |FATAL" "$WORK/pgloader.log" | head -5
    tail -5 "$WORK/pgloader.log"
    echo "pgloader failed." >&2
    exit 1
fi
grep "Total import time" "$WORK/pgloader.log"

FAIL=0
for t in $(mysqlc -e "SHOW TABLES;"); do
    m=$(mysqlc -e "SELECT COUNT(*) FROM \`$t\`;" | tr -d '[:space:]')
    p=$(pg -d "$PG_TARGET" -tAc "SELECT COUNT(*) FROM public.\"$t\";" 2>/dev/null | tr -d '[:space:]')
    if [ "$m" != "$p" ]; then echo "MISMATCH $t: mysql=$m pg=$p"; FAIL=1; fi
done
[ "$FAIL" = "0" ] && echo "row counts match on every table"
pg -d postgres -q -c "DROP DATABASE IF EXISTS ${PG_TARGET}_pristine;" -c "CREATE DATABASE ${PG_TARGET}_pristine TEMPLATE $PG_TARGET;" 2>&1 | grep -v NOTICE || true

step "4. 5.x migrate on the converted data"
echo "pending migrations: $(app "DB_DATABASE=$PG_TARGET php artisan migrate:status" | grep -c Pending || true)"
app "DB_DATABASE=$PG_TARGET APP_KEY='$V4_APP_KEY' php artisan migrate --force" > "$WORK/migrate.log" 2>&1 || {
    grep -E "FAIL|SQLSTATE|Exception" "$WORK/migrate.log" | head -5
    echo "5.x migrations failed on converted v4 data." >&2
    exit 1
}
echo "all migrations ran"

step "5. import:verify"
app "DB_DATABASE=$PG_TARGET APP_KEY='$V4_APP_KEY' php artisan import:verify" 2>&1 | grep -v "Deprecated" || FAIL=1

step "6. schema parity with a fresh 5.x install"
pg -d postgres -q -c "DROP DATABASE IF EXISTS $PG_FRESH WITH (FORCE);" -c "CREATE DATABASE $PG_FRESH OWNER $PG_USER;" 2>&1 | grep -v NOTICE || true
app "DB_DATABASE=$PG_FRESH php artisan migrate --force -q"
SCHEMA_SQL="
SELECT 'column '||table_name||'.'||column_name||' '||data_type||coalesce('('||character_maximum_length||')','')
       ||' nullable='||is_nullable||' default='||coalesce(regexp_replace(column_default, 'nextval.*', 'nextval'), '-')
  FROM information_schema.columns WHERE table_schema = 'public'
UNION ALL SELECT 'index '||indexname||' '||regexp_replace(indexdef, 'INDEX \\S+ ON', 'INDEX ON')
  FROM pg_indexes WHERE schemaname = 'public'
UNION ALL SELECT 'constraint '||conname||' '||conrelid::regclass||' '||pg_get_constraintdef(oid)
  FROM pg_constraint WHERE connamespace = 'public'::regnamespace
ORDER BY 1;"
pg -d "$PG_FRESH" -tA -c "$SCHEMA_SQL" > "$WORK/fresh.schema"
pg -d "$PG_TARGET" -tA -c "$SCHEMA_SQL" > "$WORK/upgraded.schema"
# A fresh install names the addresses primary key ip_addresses_pkey1, because
# Postgres suffixed it when a later migration's table name collided. Only the
# name differs, so it is not drift.
if diff <(sed 's/ip_addresses_pkey1/ip_addresses_pkey/g' "$WORK/fresh.schema") "$WORK/upgraded.schema" > "$WORK/schema.diff"; then
    echo "schema matches a fresh install"
else
    echo "schema drift (< fresh 5.x, > upgraded from v4):"; cat "$WORK/schema.diff"; FAIL=1
fi

echo ""
if [ "$FAIL" = "0" ]; then
    echo "RESULT: PASS"
else
    echo "RESULT: FAIL"
fi
exit "$FAIL"
