#!/usr/bin/env bash
#
# Cross-engine migration harness: proves the MySQL 8.0 -> Postgres 17 conversion
# step of the v10 cutover is lossless, on the real application schema.
#
# What it does (all self-contained, nothing touches your dev database):
#   1. Boots a throwaway MySQL 8.0 container on ddev's docker network.
#   2. Runs the full migration suite + a representative seed into it.
#   3. Renders the pgloader recipe (database/cutover/v4-to-v10.load) and runs
#      it, copying MySQL -> a scratch Postgres database.
#   4. Verifies NO DATA LOSS: exact COUNT(*) per table on both sides, plus
#      per-row content checks of the type-risky columns (bool, bigint, json).
#   5. Tears the throwaway resources down.
#
# Requires: docker, a running ddev project (for `ddev exec artisan` + the
# Postgres container). Run from the repo root:  bash database/cutover/verify.sh
#
set -euo pipefail

# --- config (defaults match a standard ddev "convoy" project) ---------------
PROJECT="${DDEV_PROJECT:-convoy}"
NETWORK="${DDEV_NETWORK:-ddev-${PROJECT}_default}"
PG_CONTAINER="${PG_CONTAINER:-ddev-${PROJECT}-db}"
PG_USER="${PG_USER:-db}"
PG_PASS="${PG_PASS:-db}"

MYSQL_CONTAINER="pgloader-src-mysql"
MYSQL_DB="convoy_v4"
MYSQL_USER="root"
MYSQL_PASS="root"
PG_TARGET="pgloader_target"

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
LOAD_TEMPLATE="$REPO_ROOT/database/cutover/v4-to-v10.load"
RENDERED="$(mktemp -t pgloader.load.XXXXXX)"

# pgloader image. The official `dimitri/pgloader` is published amd64-only, and
# pgloader is an SBCL (Lisp) binary, so it only runs where amd64 can be executed:
# natively on x86_64 hosts, or under a *complete* emulator like macOS Docker
# Desktop's Rosetta 2. Under Linux qemu-user emulation (e.g. an arm64 CI/sandbox
# host) SBCL segfaults, so the official image is unusable there. Override with
#   PGLOADER_IMAGE=<image>   to point at a native-arch pgloader for such hosts.
# NOTE: Debian's packaged pgloader (3.6.7~devel) predates MySQL 8.0's collation
# IDs and dies with "N fell through ECASE expression" on an 8.0 source DB — so a
# self-built Debian image is NOT a drop-in substitute; use a current pgloader.
PGLOADER_IMAGE="${PGLOADER_IMAGE:-dimitri/pgloader:latest}"

MYSQL_ENV="DB_CONNECTION=mysql DB_HOST=$MYSQL_CONTAINER DB_PORT=3306 DB_DATABASE=$MYSQL_DB DB_USERNAME=$MYSQL_USER DB_PASSWORD=$MYSQL_PASS"

pg()    { docker exec "$PG_CONTAINER" env PGPASSWORD="$PG_PASS" psql -U "$PG_USER" "$@"; }
mysqlc(){ docker exec "$MYSQL_CONTAINER" mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -D "$MYSQL_DB" -N "$@" 2>/dev/null; }

cleanup() {
    echo "--- cleanup ---"
    docker rm -f "$MYSQL_CONTAINER" >/dev/null 2>&1 || true
    pg -d postgres -c "DROP DATABASE IF EXISTS $PG_TARGET WITH (FORCE);" >/dev/null 2>&1 || true
    rm -f "$RENDERED"
}
trap cleanup EXIT

echo "=== 1. boot throwaway MySQL 8.0 ==="
docker rm -f "$MYSQL_CONTAINER" >/dev/null 2>&1 || true
docker run -d --name "$MYSQL_CONTAINER" --network "$NETWORK" \
    -e MYSQL_ROOT_PASSWORD="$MYSQL_PASS" -e MYSQL_DATABASE="$MYSQL_DB" \
    mysql:8.0 --default-authentication-plugin=mysql_native_password >/dev/null
# `mysqladmin ping` answers OK during MySQL 8's two-phase startup, before the
# server actually accepts DDL — so gate on a real query against the target DB.
ready=0
for i in $(seq 1 120); do
    if docker exec "$MYSQL_CONTAINER" mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -D "$MYSQL_DB" -e "SELECT 1;" >/dev/null 2>&1; then
        ready=1; break
    fi
    sleep 1
done
[ "$ready" = "1" ] || { echo "MySQL did not become ready in time." >&2; exit 1; }
echo "MySQL ready."

echo "=== 2. migrate + seed the real schema into MySQL ==="
ddev exec "$MYSQL_ENV php artisan config:clear" >/dev/null
ddev exec "$MYSQL_ENV php artisan migrate:fresh --seed --seeder=PgloaderHarnessSeeder --force" >/dev/null
echo "Seeded."

echo "=== 3. render recipe + run pgloader (MySQL -> Postgres) ==="
pg -d postgres -c "DROP DATABASE IF EXISTS $PG_TARGET WITH (FORCE);" >/dev/null
pg -d postgres -c "CREATE DATABASE $PG_TARGET OWNER $PG_USER;" >/dev/null
sed -e "s|\${MYSQL_URL}|mysql://$MYSQL_USER:$MYSQL_PASS@$MYSQL_CONTAINER/$MYSQL_DB|" \
    -e "s|\${PG_URL}|postgresql://$PG_USER:$PG_PASS@db/$PG_TARGET|" \
    "$LOAD_TEMPLATE" > "$RENDERED"
echo "using pgloader image: $PGLOADER_IMAGE"
PGLOADER_LOG="$(mktemp -t pgloader.run.XXXXXX)"
if ! docker run --rm --network "$NETWORK" -v "$RENDERED:/load/rendered.load:ro" \
        "$PGLOADER_IMAGE" pgloader /load/rendered.load >"$PGLOADER_LOG" 2>&1; then
    tail -5 "$PGLOADER_LOG"
    echo ""
    if grep -qiE "exec format error|CORRUPTION WARNING in SBCL|Memory fault|maximum interrupt nesting|ldb>" "$PGLOADER_LOG"; then
        echo "pgloader could not execute: '$PGLOADER_IMAGE' is amd64-only and this" >&2
        echo "Docker host cannot run amd64 (no Rosetta-grade emulation). Run this on" >&2
        echo "an x86_64 host / macOS Docker Desktop, or set PGLOADER_IMAGE to a" >&2
        echo "native-arch pgloader (>= 3.6.9 for MySQL 8.0 collation support)." >&2
    elif grep -qiE "fell through ECASE" "$PGLOADER_LOG"; then
        echo "This pgloader is too old for a MySQL 8.0 source (unknown collation ID)." >&2
        echo "Set PGLOADER_IMAGE to a pgloader >= 3.6.9." >&2
    fi
    rm -f "$PGLOADER_LOG"; exit 1
fi
tail -3 "$PGLOADER_LOG"; rm -f "$PGLOADER_LOG"

echo ""
echo "=== 4. verify no data loss ==="
FAIL=0
printf "%-42s %8s %8s  %s\n" "table" "mysql" "pg" "status"
for t in $(mysqlc -e "SHOW TABLES;"); do
    m=$(mysqlc -e "SELECT COUNT(*) FROM \`$t\`;" | tr -d '[:space:]')
    p=$(pg -d "$PG_TARGET" -t -A -c "SELECT COUNT(*) FROM \"$t\";" 2>/dev/null | tr -d '[:space:]')
    { [ "$m" = "0" ] && [ "$p" = "0" ]; } && continue
    st="OK"; if [ "$m" != "$p" ]; then st="*** MISMATCH ***"; FAIL=1; fi
    printf "%-42s %8s %8s  %s\n" "$t" "$m" "$p" "$st"
done

# Per-row content checks of the conversion-risky column types.
check_identical() {
    local label="$1" mysql_sql="$2" pg_sql="$3"
    if diff <(mysqlc -e "$mysql_sql") \
            <(pg -d "$PG_TARGET" -t -A -F$'\t' -c "$pg_sql" 2>/dev/null) >/dev/null; then
        echo "content OK   $label"
    else
        echo "content FAIL $label"; FAIL=1
    fi
}
echo ""
check_identical "nodes.verify_tls (bool)" \
    "SELECT id,verify_tls FROM nodes ORDER BY id;" \
    "SELECT id, CASE WHEN verify_tls THEN 1 ELSE 0 END FROM nodes ORDER BY id;"
check_identical "nodes.memory (bigint)" \
    "SELECT id,memory FROM nodes ORDER BY id;" \
    "SELECT id, memory FROM nodes ORDER BY id;"
check_identical "activity_logs.properties (json)" \
    "SELECT id,JSON_UNQUOTE(JSON_EXTRACT(properties,'\$.nested.label')) FROM activity_logs ORDER BY id;" \
    "SELECT id, properties->'nested'->>'label' FROM activity_logs ORDER BY id;"

echo ""
if [ "$FAIL" = "0" ]; then
    echo "RESULT: PASS — the MySQL -> Postgres conversion preserved every row and value."
else
    echo "RESULT: FAIL — see mismatches above."
fi
exit "$FAIL"
