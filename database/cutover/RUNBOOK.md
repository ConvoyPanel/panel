# v4 → v10 production cutover runbook

Moves a live **v4 (MySQL 8.0)** database onto **v10 (Postgres 17)**. This is a
one-time, operator-run cutover with a maintenance window — **not** a rolling
upgrade. Two hard changes stack:

1. **Engine conversion** MySQL → Postgres (data lives in MySQL; v10 runs on
   Postgres). `artisan migrate` never moves data between engines, so a
   dedicated tool (**pgloader**) copies + type-converts the rows first.
2. **24 breaking rename migrations** (`address_pools`→`address_block_groups`,
   `ip_addresses`→`addresses`, `address`→`ip`, nodes `name`↔`cluster`,
   `secret`→`token_secret`, templates/deployments restructuring, …). These are
   ordinary Laravel migrations and run **after** the data is in Postgres.

So the order is always: **pgloader (engine) → `artisan migrate` (schema)**.

## Impact on users

- **End customers' VMs keep running** — Proxmox and the workloads are separate
  from the panel DB. Only *panel access* is down during the window.
- **The operator** runs this once, following the steps below.
- **Fresh v10 installs need none of this** — they start on Postgres already.

## Confidence: the engine step is validated

`database/cutover/verify.sh` proves the pgloader conversion is lossless on the
real application schema: it seeds a MySQL copy, runs the pgloader recipe into a
scratch Postgres DB, and asserts exact per-table `COUNT(*)` equality plus
per-row content checks of the conversion-risky types (tinyint→boolean, bigint,
JSON). Run it any time — it is fully self-contained and never touches dev data:

```bash
bash database/cutover/verify.sh   # → RESULT: PASS
```

The pgloader recipe itself is `database/cutover/v4-to-v10.load` (credential-
free template; connection URLs are injected at run time).

## Cutover procedure

> Rehearse the whole thing against a **restored copy of prod** first (see "Dry
> run"), and repeat until clean, before touching real prod.

1. **Announce + enter maintenance mode.** Stop the app, Horizon, and the
   scheduler so nothing writes to MySQL mid-copy.
2. **Full MySQL backup** — the rollback anchor. `mysqldump --single-transaction
   --routines --triggers <db> > v4-prod-$(date +%F).sql`. Verify it restores.
3. **Provision the empty Postgres 17 target** and an empty database.
4. **Run pgloader** (engine conversion). Render the recipe with real
   connection strings and run it (the harness shows the exact form):
   ```bash
   sed -e "s|\${MYSQL_URL}|mysql://USER:PASS@MYSQLHOST/DB|" \
       -e "s|\${PG_URL}|postgresql://USER:PASS@PGHOST/DB|" \
       database/cutover/v4-to-v10.load > /tmp/cutover.load
   pgloader /tmp/cutover.load
   ```
   pgloader recreates the v4 tables in Postgres, converts types, copies rows,
   and rebuilds indexes/PKs/FKs/sequences. It also copies the `migrations`
   table, so Laravel knows exactly which migrations prod had already applied.
5. **Point v10 at Postgres** (`.env`: `DB_CONNECTION=pgsql`, host/db/creds).
6. **Apply the rename migrations.** First preview exactly what will run
   (`php artisan migrate:status` — everything prod already ran should show
   *Ran*, only the v10 renames *Pending*), then `php artisan migrate --force`.
   `--force` only skips the interactive "you're in production" confirmation so
   the command runs unattended — it does **not** change what the migrations do.
   The real safety here is step 2's backup and the dry run, not that prompt.
   Only the pending migrations (the v10 renames) execute, on the converted data.
7. **Smoke test** — run the Phase-1 feature suite / manual happy-path against
   the migrated DB (IPAM, nodes, templates, servers, backups). Spot-check row
   counts against the pre-cutover backup.
8. **Exit maintenance mode.**

## Rollback

Until step 8, rollback is: point `.env` back at the (untouched) MySQL, restart
the v4 app, drop the Postgres target. The MySQL source is only ever *read* by
pgloader, so it remains a valid rollback target the entire time. Keep the step-2
dump regardless.

## Dry run (required before real cutover)

Restore a prod snapshot into a scratch MySQL, run steps 4–7 against it, and diff
row counts / spot-check IPAM + node + template + server records against the
source. Repeat until clean. `verify.sh` automates the engine-conversion half of
this on synthetic data; the dry run repeats it on real prod-shaped data.

## Known cross-engine gotchas

- **`renameColumn('x','x')` no-ops**: MySQL tolerates them, Postgres rejects
  them. One was already found + fixed in
  `2024_10_10_033133_update_backup_snapshot_limit_columns_on_servers_table.php`.
  Audit any new migration for the same pattern before cutover.
- **Booleans**: MySQL `tinyint(1)` → Postgres `boolean`. Handled by the CAST
  rule in the pgloader recipe (Laravel's Postgres schema expects real booleans).
- **Timestamps**: converted to `timestamptz`; confirm the app treats stored
  times as UTC (it does).
- **Reconcile `develop`'s newer commits** (hybrid backport) before cutover so
  prod users don't regress on v4 fixes made since the merge-base.
