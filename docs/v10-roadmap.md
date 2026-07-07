# Roadmap: Ship v10 (`next`) as the new trunk

> This is the roadmap of record for the v10 effort. It originated as
> `~/.claude/plans/help-me-plan-a-cryptic-peacock.md` (outside the repo, not always
> accessible), so it's checked in here. Progress against these phases is tracked in
> [v10-next-handoff.md](v10-next-handoff.md).

## Context

`next` (v10) has been a ~20-month parallel rewrite of the Proxmox panel; `develop` (v4)
is the stable branch in production with **live users and data**. The rewrite stalled in a
"vicious refactor loop" — every architectural lesson triggered another round of rework, so
`next` was perceived as failed/unfinished.

**Exploration contradicts that perception.** `next` is ~90% done, not a skeleton:

- **Frontend is essentially complete.** TanStack Router fully adopted (file-based routes,
  zero old-router remnants), shadcn migration ~100% (no styled-components/MUI left), full
  Admin + Client screen coverage with loading/empty states, modals, forms. No dead pages.
- **Backend "broken controllers" is contained**, not systemic — mostly one orphaned legacy
  route file (`routes/api-application.php` → deleted `Admin\AddressPools\AddressController`)
  running in parallel with the correct `routes/api-admin.php`, plus a couple stale filter classes.
- Version bumps are done: Laravel 12, React 19, Vite 7, Tailwind 4, Spatie TS Transformer +
  Wayfinder codegen wired into `predev`/`prebuild`.

**Decisions made in interview:**

1. **Adopt `next` as the new trunk** and drive it to shippable via a *finite* checklist — no
   re-architecting. This is the mechanism that breaks the refactor loop: define DONE.
2. **Staged cutover** for the live v4 prod users/data (migration correctness is a hard requirement).
3. **Solo dev, commercializing** → optimize for time-to-revenue and stability.
4. **Cloudflare-style UI redesign is a fast-follow**, not a launch blocker — launch on the
   current (functional) v10 UI.
5. **Nothing major left to *build*** — v10 is feature-complete for sale. The gap is
   stability, prod migration, and polish.

**Intended outcome:** a stable v10 in production for existing customers, then commercial
launch on the current UI, with the redesign and internal-quality refactors as fast-follows.

---

## Guiding rule (to break the loop)

> **No architectural change is allowed to enter a phase unless it is on that phase's checklist.**
> Improvements you discover go to the backlog (Phase 5+), not into the current branch.
> "Done" = the checklist boxes are ticked and tests are green — not "the code feels right."

---

## Phase 0 — Dev loop & CI green (enabler; your stated first focus)

Goal: a frictionless inner loop so every later phase is faster. **Timebox this — do not let it balloon.**

- **ddev adopted + Postgres switch — DONE (verified 2026-07-04).** `.ddev/config.yaml` +
  `ddev/ddev-redis` add-on now run the full stack on **Postgres 17**: web (nginx-fpm, HTTP 200),
  migrations green, Redis cache/queue/session, Horizon + `schedule:work` as `web_extra_daemons`,
  and `ddev npm run build` runs codegen (Spatie TS transformer + Wayfinder) → vite build. This
  replaces the compose `app`/`horizon`/`scheduler`/`redis`/`database`/`logrotate` services and
  the hacky Makefile `db-snapshot`/`db-restore` (now `ddev snapshot` / `ddev snapshot restore`).
  - Config wiring: DB/redis/mail set via `web_environment` (real env vars override `.env.next`
    without disturbing the compose flow on `develop`); `ext-gmp` added via `webimage_extra_packages`;
    `types:generate` de-`docker compose`-d to bare artisan (runs in-container via `ddev npm`).
  - **Keep `dockerfiles/app` (FrankenPHP)** — it's the seed for the planned production image.
    `docker-compose.yml` + `Makefile` are now redundant for local dev on `next` but left in place.
  - **Remaining follow-ups (small):** (1) validate Vite HMR via `ddev npm run dev` — port 3000 is
    exposed (`web_extra_exposed_ports`) but `server.hmr.host` in `vite.config.ts` still says
    `localhost`; (2) document the `ddev start` → `ddev npm install` → `ddev artisan migrate` setup
    path in the README.
- **Codegen reliability.** Confirm `types:generate` (Wayfinder + `spatie/laravel-typescript-transformer`
  → `resources/scripts/wayfinder/` and `resources/scripts/types/generated.d.ts`) runs cleanly
  from `make dev` and in CI. This is the boilerplate-reduction win worth protecting.
- **CI green on `next`** (PHPStan 2.1, tests, typecheck, build) before anything else lands.

Critical files: `Makefile`, `docker-compose.yml`, `dockerfiles/app`, `package.json` (`predev`/`prebuild`),
`app/Providers/TypeScriptTransformerServiceProvider.php`.

---

## Phase 1 — Backend to green (unbreak the IPAM fallout)

Goal: every controller serves a full request cycle; establish a hard "backend is green" gate.

- **Remove the orphaned route file.** `routes/api-application.php` (registered at
  `bootstrap/app.php:40`) still wires `Admin\AddressPools\AddressController` — a deleted class.
  Reconcile so only the current route files are registered: `api-admin.php` (uses
  `AddressBlockController` / `AddressBlockGroupController` / `AddressController`), `api-client.php`,
  `api-auth.php`. Delete `api-application.php` once its routes are confirmed dead/duplicated.
- **Delete stale "pool"-era classes** or repoint them: `app/Models/Filters/FiltersAddressPoolWildcard.php`,
  `FiltersServerByAddressPoolId.php` (new code uses `FiltersAddressWildcard`).
- **Smoke-test every controller end-to-end** — Auth, Admin (Nodes, Locations, Templates,
  Servers, IPAM/AddressBlocks, Dashboard), Client (Server detail: backups/graphs/rebuild,
  Security, Deployments). One happy-path feature test per controller is the "green" definition.

Critical files: `routes/api-application.php`, `routes/api-admin.php`, `bootstrap/app.php`,
`app/Http/Controllers/Admin/**`, `app/Http/Controllers/Client/**`, `app/Models/Filters/**`.

---

## Phase 2 — Proxmox config-push safety (protects customer VMs)

Goal: close the type-safety gap you flagged — updates must not silently corrupt config.

**Problem (confirmed):** `ProxmoxConfigRepository::getConfig()` parses PVE's flat format into
DTOs via `fromRaw()`, but `update()` re-emits whole sub-objects through
`NetworkDeviceData::toProxmoxString()` etc. (see `ServerNetworkService::syncNetworkDeviceConfig()`,
`ServerNetworkBandwidthService`, `CloudinitService`, `AllocationService`). This parse→mutate→re-emit
round-trip must stay perfectly symmetric or fields silently drop.

- **Send only changed keys.** PVE's `POST .../config` accepts *partial* updates — you don't
  need to resend the whole object. Build update payloads from just the fields being mutated
  (a typed "config update" DTO / per-field setters that emit minimal payloads), instead of
  re-serializing a full fetched DTO. This directly removes the "we might undo an un-modeled
  field" risk you described.
- **Golden-master round-trip tests.** For real PVE config fixtures, assert
  `fromRaw(x) → toProxmoxString → fromRaw` is stable and drops nothing. This is the safety net
  that lets you trust the DTO layer.
- **Fill the `fromRaw()` TODOs** (`app/Data/Server/Proxmox/Config/ServerConfigData.php`) *only*
  for fields you actually mutate — not exhaustively (resist the loop).

Critical files: `app/Repositories/Proxmox/Server/ProxmoxConfigRepository.php`,
`app/Data/Server/Proxmox/Config/*Data.php`, `app/Services/Servers/{ServerNetworkService,ServerNetworkBandwidthService,CloudinitService,AllocationService}.php`.

---

## Phase 3 — Production migration & staged cutover (the hard requirement)

Goal: move live v4 data onto v10's schema with zero data loss and a rollback path.

- **Now also a cross-engine migration: prod MySQL 8.0 → Postgres 17.** The full migration suite
  runs clean on Postgres (verified in Phase 0), so `next` code is engine-portable — but existing
  prod *data* lives in MySQL. Cutover must convert engines (use **`pgloader`**, or dump/transform/
  import), on top of the schema renames below. Audit remaining migrations for MySQL-tolerant
  patterns Postgres rejects — one already found and fixed: a no-op `renameColumn('x','x')` in
  `2024_10_10_033133_update_backup_snapshot_limit_columns_on_servers_table.php`.
- **The 24 `next` migrations are breaking renames**, not additive (address_pools→address_block_groups,
  ip_addresses→addresses, `address`→`ip`; nodes `name`↔`cluster` swap, `secret`→`token_secret`;
  templates restructuring; new `deployments`/`storages`/`network_interfaces`/`passkeys`; the
  `snapshots` feature was added then removed). They ship up/down, but data must be preserved.
- **Dry-run on a copy of prod.** Restore a prod snapshot into a scratch DB, run the full
  migration set, and diff row counts / spot-check IPAM + node + template + server records.
  Repeat until clean. Use the existing `make db-snapshot`/`db-restore` tooling.
- **Reconcile `develop`'s 39 newer commits** (hybrid backport): decide which v4 fixes/features
  since the 2024-11 merge-base must be carried onto `next` so prod users don't regress at cutover.
- **Cutover runbook:** full backup → maintenance window → migrate → smoke test (Phase 1 suite
  against prod) → documented rollback. Optionally canary a migrated copy behind staging first.

Critical files: `database/migrations/2025_*`, `database/migrations/2026_03_10_remove_snapshots_feature*`,
`Makefile` (snapshot/restore targets).

---

## Phase 4 — Commercial launch on current UI (fastest to revenue)

Goal: charge customers. No new subsystems needed (v10 is feature-complete for sale).

- Ship on the **current functional v10 UI**. Focus on stability + polish, not redesign.
- **Passkeys — defer, don't touch.** The hand-rolled `web-auth/webauthn-lib` implementation
  (~570 LOC across `app/Actions/Auth/**`, `PasskeyController`, `PasskeyLoginController`,
  `PasskeySerializer`) works. Migrating to `spatie/laravel-passkeys` is a net-new dependency
  and a Phase-5 liability-reduction task — not a launch blocker.
- **Data-layer refactor — "convert as you touch," don't gate launch.** 54 of 55 hooks still
  use the hand-written SWR-compat shim (`resources/scripts/lib/swr.ts`) + raw axios with
  hardcoded URLs; only `features/servers` uses the target pattern (Wayfinder route objects +
  `apiFetch` + `features/`). This is maintainability debt, not a bug — migrate opportunistically.

Critical files: `resources/scripts/lib/{swr.ts,swr-mutation.ts,axios.ts,api.ts}`,
`resources/scripts/features/servers/api.ts` (the reference pattern).

---

## Phase 5 — Post-launch fast-follows (backlog; this is where "improvements" live)

Ordered by user-visible value:

1. **Cloudflare-style UI redesign.** Build a design system on the *existing* shadcn foundation
   (`resources/scripts/components/ui/`, `components.json`, Tailwind v4 tokens) and restyle
   screens incrementally. The finished frontend is why adopting `next` as trunk pays off here.
2. **Deployment progress tracker refactor** (you flagged it as hard to follow). Today: state is
   spread across `Deployment` + `DeploymentStep` tables, a dynamically-assembled `Bus::chain`,
   lifecycle closures from `ManagesDeploymentLifecycle`, an out-of-band cache UPID coupling
   `BuildServerJob`→`WaitUntilVmIsCreatedJob`, and magic-number `progress_total`s. Direction:
   (a) declare each deployment type's steps + weights in **one recipe/definition** instead of
   inline magic numbers; (b) move the PVE UPID onto the `DeploymentStep` row (kill the cache
   coupling); (c) add real-time push via `ShouldBroadcast` + Laravel Reverb instead of the
   frontend polling `DeploymentController`. Files: `app/Actions/Server/{BuildServerAction,RebuildServerAction}.php`,
   `app/Jobs/Server/{BuildServerJob,WaitUntilVmIsCreatedJob,ConfigureVmJob}.php`,
   `app/Traits/Actions/ManagesDeploymentLifecycle.php`, `app/Models/{Deployment,DeploymentStep}.php`.
3. **Finish the data-layer migration** to Wayfinder + `apiFetch` everywhere; delete the SWR shim.
4. **Passkeys → `spatie/laravel-passkeys`** after a parity spike (validate it supports your
   passkey-only login + confirm-identity flows before committing).

---

## Verification

- **Phase 0:** `make dev` boots the full stack; `types:generate` regenerates
  `wayfinder/` + `types/generated.d.ts` with no drift; CI green on `next`.
- **Phase 1:** feature-test suite exercises every Admin + Client controller happy path; no
  route resolves to a missing class (`php artisan route:list` clean).
- **Phase 2:** golden-master round-trip tests pass on real PVE fixtures; a live config edit
  (e.g. toggle NIC firewall) changes *only* the intended key on the VM.
- **Phase 3:** migration dry-run against a restored prod snapshot preserves all IPAM/node/
  template/server records; cutover runbook rehearsed end-to-end with a tested rollback.
- **Phase 4:** a brand-new customer can sign up, provision a server, and be billed on a
  staging environment.

---

## Open items for you to confirm

- **Real-time deploy progress**: is polling `DeploymentController` currently good enough for
  launch, or is the janky progress UX something customers will see immediately? If the latter,
  promote the Phase-5 tracker refactor's broadcasting piece earlier.
- **Reverb / websockets**: are you open to adding Laravel Reverb for push (Phase 5 item 2), or
  do you want to stay poll-only to reduce infra?
