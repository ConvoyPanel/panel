# v10 (`next`) rewrite — working handoff

Living notes for the effort to ship `next` (v10) as the new trunk. Update as phases land.
Roadmap of record: [v10-roadmap.md](v10-roadmap.md) (5 phases) — checked into the repo (it
originated as `~/.claude/plans/help-me-plan-a-cryptic-peacock.md`, which isn't reachable from
every environment). This file tracks *what's actually done* and *what to pick up next*, so a
cold start doesn't re-derive it.

Last updated: 2026-07-07

---

## Where we are

- **Phase 0 (dev loop / CI green)** — DONE. ddev runs the full stack on **Postgres 17** (web
  nginx-fpm, Redis cache/queue/session, Horizon + `schedule:work` as `web_extra_daemons`,
  codegen via `ddev npm run build`). Replaces the old compose services + Makefile snapshot
  hack (now `ddev snapshot` / `ddev snapshot restore`). Follow-ups still open: validate Vite
  HMR (`server.hmr.host` still `localhost` in `vite.config.ts`); document the
  `ddev start → ddev npm install → ddev artisan migrate` path in the README.
- **Phase 1 (backend to green)** — DONE. Orphaned `routes/api-application.php` reconciled,
  stale pool-era filter classes removed, dozens of real bugs fixed (route binding uuid/bigint
  on Postgres, backup semantics, storage refactor, ISO storage, node update,
  `StorageContentType` `->toProxmoxString()`, `AddressBlockGroupToInterface` `$incrementing=false`).
  Suite green.
- **Phase 2 (Proxmox config-push safety)** — effectively DONE. Config digest optimistic concurrency
  threaded through every read-modify-write path; the property-list DTO codec refactor landed for
  every DTO that fits it (NIC, tpmstate, USB, disk-partial); redundant NIC writes filtered; and the
  golden-master round-trip safety net is in place. See the per-topic sections below. Remaining
  polish is minor (the `LocationFactory` flakiness under "Known flakiness"). Next hard requirement is
  **Phase 3** (prod migration) — pgloader cross-engine tooling + a validated no-data-loss harness now
  landed under `database/cutover/`.

**Test suite:** `ddev artisan test --compact` → 74 passed (182 assertions) as of this writing.

---

## Phase 2 — in progress

### Landed (committed earlier this branch)
- **Config digest for optimistic concurrency.** `ServerConfigData` carries `?string $digest`
  (populated from `$get('digest')` in `fromRaw`). `ProxmoxConfigRepository::update(array $payload,
  ?string $digest = null)` merges the digest into the payload; PVE rejects the write with a digest
  error if config changed underneath us, which we catch (`isConfigModifiedError`) and rethrow as
  `ConfigModifiedException` (409, no previous attached so nothing sensitive leaks). Digest is
  threaded through `ServerNetworkService` + `ServerNetworkBandwidthService`.
  Commits: `774d7275` (echo digest), `ef755308` (surface mismatch as 409).
- **Lossless re-emit of un-modeled sub-keys.** `NetworkDeviceData` gained an `extraProperties`
  array that preserves unknown `key=val` sub-keys across parse→mutate→re-emit, so a field we
  don't model isn't silently dropped on write. Commit: `d9576ba2`.

### Exception refactor (DONE)
Replaced the legacy `DisplayException` base with native Symfony `HttpException`s + a curated
error-code contract. **Design constraints (from the user, preserve these):**
1. Client must get a real HTTP status, never a generic 500 — hence Symfony HttpExceptions.
2. Must not bubble up sensitive info — messages are curated; no `$previous` attached where it
   could leak (e.g. `ConfigModifiedException`, `NextVMIDRetrievalException` sets a safe message).
3. **Explicit error codes only — NO classname fallback.** Auto-deriving a code from the class
   name is what leaks internals in private forks; every code is a hand-written slug instead.

**Mechanism:**
- `app/Exceptions/HasErrorCode.php` — interface with `errorCode(): string`.
- `bootstrap/app.php` — a single `$exceptions->render(function (HasErrorCode $e, Request $request))`
  hook. Only fires for `$request->expectsJson()`; emits `{ message, code }` with the Symfony
  status/headers when the exception is also an `HttpExceptionInterface`, else 400.
- 18 former `DisplayException` subclasses now `extends <Symfony HttpException> implements HasErrorCode`.
  Most are `BadRequestHttpException` (400); exceptions:
  - `GuestAgentUnavailableException` → `ConflictHttpException` (409), code `guest_agent_unavailable`
  - `NextVMIDRetrievalException` → `ServiceUnavailableHttpException` (503), code `next_vmid_retrieval_failed`
  - `ConfigModifiedException` → `ConflictHttpException` (409) [from the digest work]
- `app/Exceptions/DisplayException.php` — DELETED.
- `app/Exceptions/ConvoyException.php` — KEPT (still base for `RepositoryException`,
  `DataValidationException`, `InvalidJWTException`).

Code slugs assigned: `invalid_passkey`, `invalid_passkey_json`, `invalid_passkey_public_key_credential`,
`invalid_authenticator_attestation_response`, `invalid_authentication_method`, `address_in_use`,
`too_many_backups`, `backup_locked`, `guest_agent_unavailable`, `next_vmid_retrieval_failed`,
`no_available_disk_interface`, `iso_already_mounted`, `iso_already_unmounted`, `no_unique_vmid`,
`no_unique_uuid_combo`, `insufficient_addresses`, `invalid_iso_link`, `invalid_template`.

---

## Proxmox property-list DTO codec (attribute refactor — IN PROGRESS)

Replaces the hand-written `isset(...) ? (int)... : null` parse ladders and
`if ($x !== null) $config[] = "x=$x"` emit blocks in the config DTOs with an
attribute-driven codec.

**Key design constraint:** laravel-data's own name mapping (`MapOutputName`) is *already*
the frontend/TS JSON contract for these DTOs (see `generated.d.ts` — `DiskData` uses
`is_emulating_ssd`, `bps_max`, etc.). The Proxmox property-list keys (`firewall`, `rate`,
`tag`, `link_down`) are a **second, unrelated** serialization target, so they get a
**dedicated attribute** rather than reusing `MapName`.

**Shared byte-unit scaling:** `App\Support\ByteUnit` (a 1024-based enum, `K/M/G/T`) replaces the
`* 1024 * 1024` scaling that was copy-pasted across `DiskData` (size suffix + mbps family),
`RateLimitCast`, `ServerConfigData` (memory/balloon/migrate_speed), and the `Storage` model. Parse a
Proxmox suffixed size with `ByteUnit::parseSize('32G')`; convert MiB with
`ByteUnit::Mebibytes->toBytes(...)` / `->fromBytes(...)`.

**Where it lives:** `app/Extensions/Spatie/Data/Proxmox/` (with the other laravel-data
extensions), namespace `App\Extensions\Spatie\Data\Proxmox`:
- `PropertyList` — pure codec for PVE's `head[,key=value]*` format (`explode`/`implode`).
  The positional head (`model=mac` for NICs, `volume` for disks) is DTO-specific and stays
  explicit in each DTO; the codec handles the `key=value` tail.
- `ProxmoxProperty` (attribute) — declares a property's PVE key + optional cast. Targets both
  PARAMETER and PROPERTY (promoted ctor params reflect as both; laravel-data reads the property side).
- `ProxmoxPropertyCast` (interface) — `parse(string): mixed` / `emit(mixed): ?string`.
- `Casts/PveBooleanCast` (1/0 ↔ bool), `Casts/RateLimitCast` (MiB ↔ bytes).
- `ProxmoxPropertySpec` — resolves one attributed property; infers int/string/backed-enum from
  the declared type, requires an explicit cast for bool (PVE 1/0) and other non-trivial values.
- `MapsProxmoxProperties` (trait) — reflection-driven; `mapProxmoxProperties($pairs)` →
  `[typedByPropName, leftoverPairs]`, `toProxmoxProperties()` → emitted pairs. Derives the
  "known keys" set from the attributes, so `extraProperties` losslessness no longer needs a
  hand-maintained `Arr::except([...])` exclusion list.

**Done:**
- `NetworkDeviceData` — reference implementation. Tests in `tests/Unit/Data/NetworkDeviceDataTest.php`
  (5 cases: full-field parse, full round-trip, PVE bool 1/0 + null omission, unmodeled-key preservation).
- `TpmStateDiskData` — refactored onto the codec; its ~40-line convoluted volume parser collapsed to
  a few lines, and it **gained `extraProperties` losslessness** it previously lacked (it silently
  dropped unknown keys). Tests in `tests/Unit/Data/TpmStateDiskDataTest.php` (5 cases). Note: `size`
  still truncates the PVE unit (`4M` → `4`) as before — pre-existing, and `size` is informational for
  tpmstate; left as-is to avoid changing the `int` TS contract.
- `UsbDeviceData` — refactored onto the codec for its `key=value` tail (`mapping`, `usb3` via
  `PveBooleanCast`); the ~70-line `filter_var`/`isset` parser collapsed to a few lines. Its head is
  **polymorphic** — bare `host`, `host=`, or (for a cluster-mapped passthrough) `mapping=` — so the
  head stays explicit and only the tail goes through the codec. Deliberately **no `extraProperties`
  and no `toProxmoxString`**: it's parse-only, and per the DiskData caution below, emit/losslessness
  is dead weight (and a write-path risk) without a tested push path. Tests in
  `tests/Unit/Data/UsbDeviceDataTest.php` (5 cases: bare host, `host=`+usb3, mapping-as-head,
  mapping-in-tail, absent-flag default).

Suite: 65 passed. PHPStan clean on the changed files (the full-suite 272 errors are the pre-existing
`next` baseline, not from these changes).

**Ruled out (don't fit the property-list codec):**
- `CloudinitConfigData` — not a `head[,key=value]*` string; it's assembled from *separate* top-level
  PVE config keys (`citype`, `ciuser`, `cipassword`, `sshkeys`, ...) via `Arr::get($raw, ...)`. No
  positional-head property list, so the codec doesn't apply.
- `VgaConfigData` — carries no `fromRaw`/`toProxmoxString` parser at all (plain DTO); nothing to
  refactor here.

- `DiskData` — **partially** refactored onto the codec (behavior-preserving). The 9 boolean flags
  (`ssd`, `backup`, `replicate`, `ro`, `iothread`, `snapshot`, `shared`, `detect_zeroes`,
  `scsiblock`) and the 5 string identity fields (`wwn`, `model`, `product`, `serial`, `vendor`) now
  map straight off `#[ProxmoxProperty]` — the `filter_var(...)` bool ladder and the ~40-line
  variable-init block are gone, and the manual tail `foreach` collapsed to `PropertyList::explode`.
  **Left explicit on purpose** (these don't fit the one-key-per-property model and changing them
  would alter this parse-only, frontend-facing contract):
  - `diskMediaType` — `DiskMediaType` is an **unbacked** enum (`case DISK; case CDROM;`) with a
    non-null default, so it can't go through the codec's `::from()`; stays a `match`.
  - the other enums (`format`, `cache`, `aio`, `discard`, `rerror`, `werror`, `trans`) use
    **`tryFrom() ?? default`** so an unfamiliar PVE-version value degrades to null/RAW instead of
    throwing; the codec's `::from()` would regress that robustness.
  - `size` unit suffix (`32G` → bytes), dual-unit bandwidth (`mbps` wins over `bps`, scaled to
    bytes), the `*_max_length`/`*_length` alias fallbacks.
  - the iops-family + `cyls`/`heads`/`queues`/`secs` ints, which fall back to **0** (not null) when
    absent — a pre-existing quirk kept via `(int) $get(...)`.
  Characterization tests locked current behavior first (`tests/Unit/Data/DiskDataTest.php`, 9 cases)
  and stayed green across the refactor. Still **parse-only** (no `toProxmoxString`) — do NOT add emit
  speculatively; disk re-emit is a real VM-write risk that belongs with a tested push path. One
  behavior note: the codec only parses `key=value` (PVE's actual output); the old parser's defensive
  bare-flag `?? true` path is gone, which is a non-issue for real PVE strings.

## Digest threading — rest of the write paths (DONE)

Threaded `$config->digest` through the remaining **read-modify-write** config writes, matching the
`ServerNetworkService` / `ServerNetworkBandwidthService` pattern. The key insight: digest optimistic
concurrency only helps a *read-modify-write* (where the payload or its key set is derived from a
config we just read). Blind writes whose values come from args, not from the read config, were
**deliberately left without a digest** — threading one there would force an extra `getConfig()` round
trip and raise spurious 409s on unrelated concurrent changes.

- `CloudinitService::setIpConfig` — RMW (the `ipconfig{id}` key set comes from the NICs it reads).
  Digest threaded; also dropped a redundant second `setServer()`.
- `AllocationService::mountIso` — RMW (picks the free `ide{n}` slot from the read config). Digest
  threaded from that read.
- `AllocationService::unmountIso` — RMW (deletes the interface of the disk it found). Now reads the
  full config (was `getDisks()`) so it can pass the digest.
- **Left blind (no digest, on purpose):** `CloudinitService::setHostname` / `setNameservers`,
  `ServerAuthService::setPassword` / `setSSHKeys`, `AllocationService::setBootOrder`, and the
  `cores`/`memory` write in `AllocationService::syncSettings` (values come from the `$server` model,
  not the read config).

Tests: `tests/Unit/Services/Servers/{CloudinitService,AllocationService}Test.php` assert a PVE digest
mismatch surfaces as `ConfigModifiedException` (which only fires when a digest was actually passed).
Suite: 67 passed. No new PHPStan errors (AllocationService's 2 findings are the pre-existing baseline:
`Arr::pluck` over `ServerConfigData`, `$iso->storage->name` on the base Model).

> Note: `unmountIso`/`mountIso` matched a mounted ISO via `->where('media_name', ...)`, but
> `DiskData` has no `media_name` property — the found-branch was unreachable (unmount always threw
> `IsoAlreadyUnmountedException`; mount never detected an already-mounted ISO). **FIXED**: matching is
> now `findMountedIsoDisk()` on the cdrom disk whose `volume` equals `{storage}:iso/{file_name}` (the
> exact string `mountIso` writes). Covered by `AllocationServiceTest` (mount/unmount happy + reject
> paths). `mountIso` also consolidated from two config reads to one.

## `syncNetworkDeviceConfig` redundant-write filter (DONE)

`ServerNetworkService::syncNetworkDeviceConfig` now skips NICs already in the desired state instead
of rewriting every device. Subtlety: this method sets firewall **and** mac/bridge, so a NIC that's
already firewalled might still need a mac/bridge correction — the predicate keeps a device only when
`firewall`, `mac`, or `bridge` would actually change. If nothing needs changing it returns early
rather than POSTing an empty (digest-only) update. Test:
`tests/Unit/Services/Servers/ServerNetworkServiceTest.php` drives `syncSettings` with one already-
firewalled NIC and one not, and asserts the write carries the stale NIC but never the already-correct
one (fails without the filter, which would write both). Suite: 68 passed; no new PHPStan errors.

## Golden-master round-trip tests (DONE)

`tests/Unit/Data/ProxmoxConfigRoundTripTest.php` — the Phase-2 safety net. Data-driven over a corpus
of real PVE strings for the two DTOs that emit (`NetworkDeviceData`, `TpmStateDiskData`), asserting:
1. **Idempotent re-emission** — `emit(parse(emit(parse(x)))) === emit(parse(x))`, i.e. the round-trip
   drops nothing.
2. **No field loss** — for net, the full tail key=value map (and the positional head) survive the
   first cycle unchanged; for tpmstate, every tail key survives *except* `size`, whose unit suffix
   (`4M → 4`) is the one documented, intentional normalization.
3. **Unmodeled keys preserved** — corpus includes strings with keys we don't model (`mystery=42`,
   `future_opt=xyz`), proving `extraProperties` losslessness end-to-end.

`DiskData` and `UsbDeviceData` are parse-only (no `toProxmoxString`), so they're out of scope here —
covered instead by their characterization tests. Suite: 74 passed.

## Phase 4 — data-layer migration to TanStack Query (DONE)

Phase 4 goal: converge the whole data layer on the **`features/*` reference pattern**
(`queryOptions` + `apiFetch` + Wayfinder route objects). Reference implementations:
`features/servers/api.ts` and `features/overview/api.ts`.

> **Status update (2026-07-07): DONE.** The migration is complete — the old `api/` directory is
> **gone entirely**, there are **zero raw `axios.{get,post,…}` calls** left in `resources/scripts`,
> and every domain now lives in a `features/<domain>/api.ts` module on `apiFetch` (21 such modules:
> account/{authenticator,passkeys,password}, auth/{,identity}, ipam/{,blocks,blocks/addresses},
> locations, nodes/{,network-interfaces,storages}, overview, servers/{,admin,backups,detail,state},
> template-groups/{,templates}, users). The SWR-compat shim (`lib/swr.ts`) is gone and no `swr`
> import remains anywhere. The last residue — the misnamed `swrKey` prop on `ResourceComboboxForm`
> (it feeds a TanStack `useInfiniteQuery` queryKey, not the removed shim) — was **renamed to
> `queryKey`** in commit `66c00ff8` (tsc clean, behavior-preserving).
>
> **Admin caveat (still applies going forward):** admin controllers are served under two prefixes
> (`/api/admin` + `/api/application`), so Wayfinder emits URI-keyed dictionaries — reference the
> `/api/admin` route explicitly (`Controller.method['/api/admin/…']`), see `features/locations/api.ts`
> / `features/overview/api.ts`. Client controllers (`/api/client/*`, `/api/auth/*`) have no app-API
> twin and stay clean callables.

**Landed:**
- **Admin locations → `features/locations/api.ts`.** Consolidated the whole domain (list/detail/nodes
  queries + `getLocations` callable fetcher for the combobox + create/update/delete mutations + the
  `useLocations`/`useLocation`/`useAttachedNodes` convenience hooks) into one feature module on
  `apiFetch` + Wayfinder routes. Deleted the nine `api/admin/locations/{get*,use-*,create/update/delete}.ts`
  files. Repointed 7 consumers (LocationList, LocationPicker, AttachedNodesList, Create/Edit/Delete
  modals, `locations.lazy.tsx`); component logic and the consumer-side optimistic `useQueryMutator`
  updates are untouched. Behavior-preserving. tsc clean, production build green. Commit `fb34b712`.
- **Client server backups → `features/servers/backups/api.ts`.** `backupQueries.list(serverUuid,
  params)` returns a `queryOptions` with `keepPreviousData`; preserves the two things the old
  `getBackups` did — the `rawDataToBackup` date/enum normalization and the extra `backupCount` field
  on the paginated envelope. `BackupView.tsx` now uses `useQuery(backupQueries.list(...))`, reading
  `serverUuid` from `useParams({ strict: false })` exactly as the old hook did internally. Deleted
  `api/servers/backups/{use-backups-swr,getBackups}.ts` (both were single-consumer). Behavior-
  preserving 1:1 swap: same Wayfinder-resolved URL, same transform, same response shape. tsc clean,
  production build green.

### Application API unified onto the admin route file (DONE)
`routes/api-application.php` was **not** removed by Phase 1 as earlier notes implied — it was a live
`auth:sanctum` machine-to-machine Application API reusing the `Admin\*` controllers, but it had
**drifted into a stale partial duplicate** of `api-admin.php` (missing IPAM / overview / storages /
network-interfaces; referencing template-reorder controller methods that no longer exist). Rather than
keep two files in sync, it's now **deleted**, and the single `routes/api-admin.php` is served under
**both** guards (per the user's "one file, two entry points" decision):
- `/api/admin` — the panel's web session (unchanged: `['auth', AdminAuthenticate]`).
- `/api/application` — Sanctum Bearer tokens (`['auth:sanctum', AdminAuthenticate]`). Added
  `AdminAuthenticate` here deliberately — the old application group had **no** admin check, so any
  token could hit admin endpoints; now the token's user must be `root_admin`.

So the Application API now has **full parity** with the panel from one source of truth. Session-vs-token
differences are enforced per-route: `app/Http/Middleware/DenyApiTokenAccess` gates `/tokens` (an API
token must never mint/revoke other tokens — the only session-only surface, per the user's "just token
CRUD" call). The still-live `users/{user}/generate-sso-token` route was ported into `api-admin.php`.
`tests/Feature/ApplicationApiTest.php` locks the four guarantees (token reaches shared endpoints; token
denied on `/tokens`; session admin still manages tokens; non-admin token rejected). Suite 104, PHPStan 0.

> ⚠️ **Wayfinder still emits URI-keyed dictionaries for admin controllers** — unifying didn't remove
> that. As long as `api-admin.php` is served under **two prefixes**, every admin action has two routes,
> so Wayfinder emits `{ '/api/admin/…': fn, '/api/application/…': fn }` rather than a callable. Reference
> the admin URI explicitly (e.g. `OverviewController['/api/admin/overview']()` — see `features/overview/
> api.ts`). **Client** controllers (`/api/client/*`, `/api/auth/*`) have no app-API twin and stay clean —
> prefer migrating those first. The only way to get clean admin callables would be collapsing to a single
> prefix (the deferred Option A); not worth it now.

## Phase 3 (operator cutover deliverable) — tooling DONE, not a dev gate

**Reframed 2026-07-07:** Phase 3 (the v4 MySQL 8.0 → v10 Postgres 17 cutover) is a migration path
that **downstream operators run when upgrading their own Convoy installs** — it is NOT something the
maintainer runs against prod data (he has none; he ships the software, he doesn't operate an
install). Fresh v10 installs start on Postgres and skip it entirely. So the *deliverable* is the
tooling + runbook under `database/cutover/` (RUNBOOK.md, pgloader recipe `v4-to-v10.load`, and
`verify.sh` which validates the engine conversion is lossless on synthetic data). That deliverable
is **done and committed**; it stays in the repo. There is no maintainer-side dry-run task pending —
the real, prod-data rehearsal is the operator's, on their own host.

> **`verify.sh` host-arch caveat (2026-07-07) — for operators picking a host, not a maintainer
> blocker.** The engine-conversion harness (`database/cutover/verify.sh`) is green *where pgloader
> can run amd64* — x86_64 hosts or **macOS Docker Desktop (Rosetta)**. It cannot self-verify inside
> an **arm64 Linux** sandbox: `dimitri/pgloader` is amd64-only and its SBCL/Lisp runtime segfaults
> under Linux qemu-user emulation (Rosetta handles it; qemu-user doesn't). Debian's native-arm64
> pgloader is too old (3.6.7~devel) and dies on MySQL 8.0 collation IDs ("N fell through ECASE").
> The harness takes a `PGLOADER_IMAGE` override (default unchanged) and prints a targeted diagnostic
> instead of a raw crash. Steps 1–2 (boot MySQL, migrate+seed the full v10 schema) pass everywhere.

## Next up — product / feature threads

Phases 0/1/2/4 are done; Phase 3 tooling is a shipped operator deliverable (above). The maintainer's
remaining work is the product follow-ups below (all doable in-sandbox, no prod data): the **IPAM
allocator rewrite**, **API tokens v2**, and the **deployment progress tracker refactor**. See the
"Product follow-ups" section for the researched direction on each.

> Note: the IPAM allocator rewrite's write-up says storing IPs as Postgres `inet` was "cheapest as
> part of the Phase 3 cutover." Since the cutover isn't a maintainer step, that `inet` change is now
> just a **normal `next` migration** to schedule on its own — no longer coupled to a cutover event.

### Cutover migration audit (DONE — no prod data needed)
The only migrations that actually *execute* at cutover are the **24 on `next` but not on
`develop`/prod** (pgloader copies the `migrations` table, so prod's already-run migrations are
skipped). Diffed the two branches to isolate that set and audited it for cross-engine hazards:
- **No-op `renameColumn('x','x')`** (MySQL tolerates, Postgres rejects): only the one already
  fixed in `2024_10_10_033133_...`; every other rename targets a genuinely different name. Clean.
- **`tinyint(1)`→bool / `datetime`→`timestamptz`**: handled by the pgloader CAST rules.
- **`->change()` calls** in the executing set (`update_vmid_column_type`, `make_server_status_nonnullable`,
  `change_backups_completed_at_to_errors_column`, `update_size_column_on_backups_snapshots_tables`)
  are numeric/string/boolean type-preserving changes Postgres accepts without a `USING` clause.
- **`->after()`** appears widely but Postgres' Laravel grammar silently ignores it — not a failure.
Residual risk is schema-drift between pgloader's MySQL-derived tables and Laravel's expectations,
which only the **real-data dry run** (RUNBOOK "Dry run" section) can close. `migrate:fresh` already
proves the 24 run clean on an *empty* Postgres DB (the RefreshDatabase test runs).

### `develop` → `next` reconciliation ledger (39 commits, DONE analyzing)
Backport audit of the 39 `develop`-only commits since merge-base `bdc9c413`. The same solo dev
applied most fixes to *both* branches, so nearly all are already reconciled on `next`.

> **Re-audit 2026-07-05:** `develop` has **not advanced** since this ledger was written — still
> exactly 39 commits off the same merge-base (`bdc9c413`), identical hashes, nothing new to
> backport. The re-audit did close a *coverage* gap in the ledger (not a code gap): four
> security-labeled commits `8bdecd12`/`2181f63b`/`8329e4e7`/`c01ca604` (**fix: unauth user access**
> — IPAM settings / IPAM / nodes / users) were never explicitly bucketed. They are **frontend-only
> and moot on `next`**: each wraps an old v4 route loader in `resources/scripts/routers/Admin*Router.tsx`
> in a `try/catch`, and that entire router tree was deleted in the frontend rewrite. The real
> authorization boundary is server-side — `bootstrap/app.php` gates both `/api/admin` and
> `/api/application` with `AdminAuthenticate` (→ `root_admin`) — so the client-side loader guard was
> never the actual protection. The remaining previously-unlisted commits (`c5ff6f79` merge,
> `55e2b313`/`c0ea4f13`/`059a6557` test-only, `19b4c8d4` return-type) fall in the "v4-only noise"
> bucket below.

Verified:
- **Already in `next` (security-critical):** JWT signature validation (`4cf7c953` → `next`'s
  `JWTService::decode` has `SignedWith` **and** the `app.url`→`app.key` decode-key fix, both with
  explanatory comments); revoke API tokens on admin demotion (`dc09e2df` → `UserController::update`
  has it, cleaner condition); realmtypes (`ea798dd7` → `RealmType` enum used throughout).
- **Already in `next` / superseded:** Laravel 11 upgrade (`adfd703e` → `next` is on Laravel 12);
  nameserver parse fix (`92c11ecf` → `ServerConfigData` uses `$get('nameserver', [])`);
  too_many_ips message (`b3ec26c7` → `InsufficientAddressesException`, curated code).
- **Deliberately NOT backported: guest-agent live password (`c431a7b6`/`bc9aada5`).** v4 live-set
  the password over the QEMU guest agent (Windows→`Administrator`, Linux→`root`) on top of the
  `cipassword` write. That path is now redundant: **cloudinit applies passwords on every supported OS,
  Windows included**, so `ServerAuthService::setPassword` stays a single `cipassword` write. The
  guest-agent route needs the agent running + OS-specific usernames — strictly more fragile for no
  behavioral gain — so it's intentionally dropped, not carried onto next. (Explored a backport this
  session, then reverted per that call; `next`'s `ProxmoxGuestAgentRepository::{getOsInfo,setUserPassword}`
  remain available for other uses.)
- **Genuine gaps still open (logged, not blockers):**
  1. **Admin overview dashboard + metrics endpoint (`8e1729ec`) — DONE.** Ported to next and
     re-architected for its schema/stack:
     - Backend: `GET /api/admin/overview` (admin-gated by the `api-admin` route group's
       `AdminAuthenticate` middleware — no controller/policy needed), `OverviewService` returning a
       typed `App\Data\Admin\Overview\OverviewData` tree (laravel-data, not Fractal), cached 15s.
     - Adaptations: memory/disk are MiB in the DB (`StorageSizeCast`) so aggregates convert to bytes;
       `node.disk` is gone (moved to the storages model) so disk capacity is a **fleet `storage`
       section** = committed VM disk vs. node-attached `stores_kvm` capacity, and per-node breakdown is
       memory-only; no `RESTORING_SNAPSHOT`; backups use `errors`/`completed_at`; `AddressBlockGroup`
       replaces `AddressPool`; all aggregates Postgres+MySQL portable (no `SUM(bool)`).
     - Clean DTO names (`ResourceAllocationData`, `FleetSummaryData`, `NodeSummaryData`, …); `nodes`
       emitted as a plain array via `->withoutWrapping()` so the contract isn't a nested `{data:[]}`.
     - Frontend: `features/overview/api.ts` (TanStack Query + `apiFetch` + Wayfinder route, unwrapping
       the `data` envelope) and `Admin/Dashboard/OverviewContainer.tsx` replacing the stub dashboard
       index — summary tiles, memory/storage capacity bars, server-status breakdown, address/backup/ISO
       cards, per-node memory table (shadcn components).
     - Feature test (metrics + restoring bucket + admin guard), full suite 94 passed, PHPStan zero,
       tsc clean, production build green. **Not yet visually verified in-browser.**
     - Left behind (v4-only cosmetic follow-ups `9f8eec25`/`44279c50` — SWR-revalidation tweak and
       overview card subtext — are develop-UI specific and don't apply to the rewritten next UI).
     - **Discovered en route — FIXED.** `database/factories/AddressFactory.php` was stale: it set the
       pre-IPAM-revision columns (`type`, `address`, `cidr`, `gateway`, `mac_address`) that no longer
       exist (the table is now `ip`, `prefix_length`, `address_block_id`, `server_id`, with `version`/
       `gateway`/`mac_address` as accessors reading the block), and omitted the required
       `address_block_id`, so `Address::factory()` could not create a valid row. Rewritten onto the real
       schema and paired with a **new `AddressBlockFactory`** (there was none) so `Address::factory()` is
       self-sufficient — it spins up a valid `AddressBlock` → `AddressBlockGroup` chain. Both have an
       `ipv6()` state. `OverviewControllerTest`'s hand-rolled `AddressBlock::create` + `Address::create`
       seeding now goes through the factories (exercising them in CI so they can't silently rot again),
       preserving the exact IPs/counts its assertions depend on. Verified via tinker (v4/v6/batch create)
       and the full suite (100 passed). PHPStan `app/` gate still zero (the factories add no errors; test
       files are out of PHPStan scope by config — `paths: app/`).
  2. **`ac13cefc` skip no-op Proxmox Configure tasks — DONE.** Verified `next` lacked the no-op skip in
     `AllocationService::syncSettings` (cores/memory), `CloudinitService::setHostname` (name/searchdomain),
     and `CloudinitService::setIpConfig` (ipconfig{n}) — all POSTed unconditionally, enqueuing a redundant
     PVE "Configure" task on every sync (same class as Phase 2's `syncNetworkDeviceConfig` filter).
     - `syncSettings` now diffs cores/memory against the config it already reads and writes only changed
       keys (zero extra round trip; `$config->cpu->coreCount` + `$config->memory` bytes→MiB).
     - `setHostname` diffs against `$config->name` + `$config->cloudinit->searchDomain` (searchDomain was
       already modelled on `CloudinitConfigData`) and writes only what differs.
     - `setIpConfig` needed modelling: added `App\Data\Server\Proxmox\Config\IpConfigData` (parses a PVE
       `ipconfig{n}` property string) and `CloudinitConfigData::$ipConfigs` (`Collection<int, IpConfigData>`
       keyed by NIC index, parsed in `fromRaw`). The desired string is parsed through the same codec so the
       compare is order/format-insensitive; NICs already at the target are skipped and an all-match sync
       returns before writing (no empty digest-only POST). The emitted write payload is byte-for-byte
       unchanged — only *which* NICs get written narrowed.
     - Tests: `AllocationServiceTest` (hardware no-op/changed), `CloudinitServiceTest` (ipconfig parse +
       order-insensitivity, per-NIC modelling, hostname skip/write, ipconfig skip/write). Full suite 91
       passed; PHPStan zero; `IpConfigData`/`ipConfigs` flow into `generated.d.ts` on codegen.
     - Password-job retries: `next` has `tries = 3`; `ac13cefc` raised v4 to 15 (30 s backoff) to survive
       slow disk-resize during creation. Left as a tuning decision, not blind-ported.
  3. **`22c4693e` locale validation — RESOLVED (moot).** Confirmed `next` has no i18n feature at all:
     no locale route, no `LocaleController`, no `users.locale` column, no frontend i18n (the JS
     `toLocaleString`/`localeCompare` hits are unrelated number/string formatting). The translation
     endpoint `22c4693e` validated was dropped in the rewrite — nothing to port.
- **Skipped (v4-only noise):** all CHANGELOG/docs commits, FOSSA removal, compose db/redis port
  exposure (`844b96a5`/`83279f01` — moot on ddev), IP pagination `999999` hack (`3c6200ca` — a v4
  workaround, don't carry blindly), composer security bumps (`e3416c77` — `next` has its own Laravel-12
  lock; run `composer audit` independently instead of cherry-picking).

## Product follow-ups to add to the roadmap

- **IPAM allocator rewrite + reserve-IP feature (user request 2026-07-05) — IN PROGRESS.**

  > **Slice 1 (DB-agnostic allocator) — DONE (2026-07-07), commit `20c6de73`.** Rewrote
  > `AddressAllocationService::handle` from the O(address-space) offset walk into one indexed query
  > per version over the pre-materialized free rows: `WHERE address_block_id IN (…) AND server_id IS
  > NULL ORDER BY id LIMIT n FOR UPDATE SKIP LOCKED`. Kills the unbounded loop, the double-assign
  > race (SKIP LOCKED hands concurrent allocations different rows), and the two-disagreeing-mechanisms
  > problem (now consumes `GenerateAddressesAction`'s rows instead of `firstOrCreate`-ing new ones).
  > `FOR UPDATE SKIP LOCKED` works on **both** Postgres and MySQL 8.0 → no schema change, CI stays
  > green. Contract: must run inside the caller's transaction (ServerCreationService already wraps
  > create in `DB::transaction`) so the locks reserve the still-unassigned rows until `syncAddresses`
  > stamps `server_id`. Tests: `tests/Unit/Services/Addresses/AddressAllocationServiceTest.php` (6).
  > Suite 123 passed; PHPStan `app/` zero.
  >
  > **Slice 2 (Postgres `inet` + index) — DONE (2026-07-07), commit `46d9b398`.** Postgres-only was
  > approved ([[v10-postgres-only]]; CI moved to Postgres 17 in `0a1ea758`). Migration
  > `2026_07_07_000000_convert_ip_columns_to_inet` converts `addresses.ip`, `address_blocks.base_ip`
  > and `.gateway` from varchar to native `inet` (`ALTER … USING …::inet`), and adds the partial index
  > `addresses_free_by_block_ip_idx ON addresses (address_block_id, ip) WHERE server_id IS NULL`. The
  > allocator now `ORDER BY ip` (numeric, no lexical `10.0.0.10 < 10.0.0.2` bug), served by the index
  > with no sort. **Verified with `EXPLAIN ANALYZE` on 50k rows: Index Scan on the partial index, 2
  > rows read, 7 buffer hits, no Sort, 0.03 ms** — the O(log N + n) seek, not a table scan. The `ip`
  > column still returns as a string in PHP (inet renders bare host addresses), so the TS contract
  > (`ip: string`) is unchanged. Suite 124; PHPStan zero.
  >
  > **Remaining slices (not yet started):**
  > **Slice 4 (sparse materialization for large/v6 blocks) — DONE (2026-07-07), commit `eb455c95`.**
  > Pre-materialization was the last address-space-sized operation (O(M) slots, unbounded for v6).
  > `AddressBlock::isSparse()` splits blocks at `DENSE_MAX_HOST_BITS` (2^16 units): dense blocks are
  > pre-materialized/browsable as before; sparse blocks (large v4 / any v6) are **minted on demand**.
  > `GenerateAddressesAction` skips sparse blocks (returns `sparse=true`) and drops the old v6 >10000
  > cap. The allocator reclaims free rows first (the O(log N + n) indexed path, covers dense + freed
  > sparse rows), then mints the shortfall by appending after `MAX(ip)` (served O(log N) by the unique
  > `(address_block_id, ip)` index) under a per-block `FOR UPDATE` lock — next candidate is always
  > `MAX+stride`, so it can't pre-exist (no conflict, no double-assign). **No migration needed** (cursor
  > derived from MAX(ip), not a stored column). A v6 /64 hands out its first N addresses in N index
  > lookups, never 2^64 rows. Tests: sparse v4/v6 mint + reclaim-before-mint. Suite 127; PHPStan zero;
  > tsc clean (`GeneratedAddressesData` gained `sparse`).
  >
  > **Slice 3 — address state + reserve-IP + auto-exclusions — DONE (2026-07-07), commits `75ada6ec`
  > (backend) + `6a5cac43` (frontend).** Design decisions (user-approved): reserve is **fully locked**
  > (a reserved address can't be assigned until unreserved), **bare flag** (no note/owner), and
  > network/broadcast/gateway are **auto-reserved and visible**.
  > - `AddressState` enum (`available`/`assigned`/`reserved`) + migration (`2026_07_07_000002…`) adding
  >   the `state` column (backfilled from `server_id`); the allocator partial index repointed from
  >   `WHERE server_id IS NULL` to `WHERE state = 'available'`.
  > - Allocator selects only `available`; `syncAddresses` keeps state in lock-step (available↔assigned).
  > - Auto-exclusions: `AddressBlock::systemReservedAddresses()`; `GenerateAddressesAction` marks
  >   network/broadcast/gateway reserved for dense blocks (skips network/broadcast on /31,/32 per RFC
  >   3021); sparse minting materializes the low system-reserved rows (network/gateway) so they're
  >   skipped, broadcast excluded by scale.
  > - Reserve API: `POST`/`DELETE …/addresses/{address}/reserve` (curated 409 codes
  >   `address_not_available` / `address_not_reserved`); `UpdateAddressRequest` rejects assigning a
  >   reserved address. Frontend: state badge column + Reserve/Unreserve row actions in the admin
  >   address list.
  > - Tests: `tests/Feature/Ipam/AddressReservationTest.php` (5) + allocator "never hands out reserved".
  >   Suite 133; PHPStan zero; tsc + vite build green.
  >
  > **⚠️ IPAM ALLOCATOR REWRITE COMPLETE (all 4 slices).** Allocation is O(log N + n) and proven;
  > generation is no longer address-space-sized; IPv6 blocks work; the race is gone; reserve + auto
  > exclusions shipped. Minor documented caveat: a pathologically *high* gateway in a sparse block
  > leaves lower addresses unminted (append-above-MAX) — non-issue for conventional low gateways.
  >
  > **✅ Postgres-only decision — MADE (2026-07-07).** v10 is Postgres-only; MySQL runtime support is
  > dropped. CI moved from MySQL 8.0 → Postgres 17 (`0a1ea758`), `.env.ci`/`.env.example` default to
  > `pgsql`, and the `next` branch is now in the CI push triggers. See [[v10-postgres-only]]. This
  > unblocked Slice 2 (done, above).

  ---
  Original problem analysis (kept for reference):
  Two parallel, disagreeing mechanisms
  exist today: `GenerateAddressesAction` **pre-materializes** every allocatable slot as an `Address`
  row (`server_id = null`, batched 300), but `AddressAllocationService::handle()` **ignores those free
  rows** — it loads only *assigned* IPs into memory (`whereNotNull('server_id')`) and does a linear
  `for ($i = 0; $i < $range->getSize(); ++$i)` walk of the whole address space by offset, `firstOrCreate`-ing
  the first gap. Problems:
  - **O(address-space) scan.** `getSize()` is over `prefix_length_from` — a /16 is 65k iterations per
    allocation and an **IPv6 block is 2⁶⁴+, so the loop is effectively unbounded**. This is the "detect
    unused IPs between used ones without scanning everything" concern the user raised.
  - **IPs stored as `string`** (`addresses.ip`, per `2025_05_07_194624_ipam_revision.php`) → lexical
    ordering is wrong (`10.0.0.2` > `10.0.0.10`) and no range predicates, which is *why* the code falls
    back to an in-memory arithmetic walk instead of an indexed query. **Root blocker.**
  - **No `server_id` index** (unique is only `(address_block_id, ip)`); **race condition** — two
    concurrent allocations compute the same first-free offset and the unique index silently hands the
    loser the now-assigned row (double-assignment); network/broadcast/gateway aren't excluded from the
    generated slots.

  **Direction (research-backed — this is a classic pooled-allocation problem, don't hand-roll):**
  - **Select free slots with one indexed query, not an in-memory walk.** Over pre-materialized rows:
    `WHERE address_block_id = ? AND server_id IS NULL ORDER BY ip LIMIT :n FOR UPDATE SKIP LOCKED`.
    O(log n), gaps-between-used handled inherently (a freed IP flips `server_id` back to null and
    re-enters the pool), and `SKIP LOCKED` makes concurrent allocations grab *different* rows with no
    double-assign. This is exactly **FreeRADIUS `sqlippool`**'s `alloc_find` pattern (pre-populated pool
    table + `status`/`owner`/`expiry` + `FOR UPDATE SKIP LOCKED` on Postgres) — the battle-tested
    reference. Deletes the entire range walk.
  - **Avoid NetBox's model.** `get_first_available_ip()` computes availability in application code (set
    difference) and has a documented race because it doesn't enforce DB-level uniqueness — that's
    essentially Convoy's current in-memory approach. Cautionary, not a template.
  - **Store IPs queryably** — Postgres native `inet`/`cidr` (correct ordering, arithmetic `ip + 1`,
    subnet containment `<<`, gap detection via `LEAD()`/`LAG()` window functions or `generate_series`
    minus the used set). This is the keystone and is **cheapest to do *as part of* the Phase 3
    MySQL→Postgres cutover**, not as a separate migration afterward.
  - **Materialization policy for huge blocks.** Can't pre-generate 2⁶⁴ IPv6 rows — pre-materialize
    small blocks (indexed-select above); for large/v6 blocks, sparse-store with a per-block
    cursor/high-water-mark + unique constraint + retry-on-conflict, reclaiming gaps via a free-list or a
    `lead()`-over-`inet` window query.
  - **Reserve-IP feature.** Today "assigned vs free" is just `server_id IS NULL` (binary). Add an
    address **state** (`available` / `assigned` / `reserved`, plus auto-excluded `network` / `broadcast`
    / `gateway`) with optional `reserved_by` + note; auto-allocation selects only `available`, reserved
    IPs are held out of the pool but still explicitly assignable. Small enum + migration; the
    query-based allocator already filters on it.
  - Cross-links: pairs with **VLAN #150** (both touch address blocks / network interfaces) and depends
    on **Phase 3** for the `inet` storage change. Sources: FreeRADIUS sqlippool docs; NetBox
    `get_first_available_ip` (race caveat); PostgreSQL network-address types / `inet` operators.

- **API tokens v2 — scoped abilities + two distinct token kinds (user request 2026-07-05) — PARTLY
  DONE.**

  > **Application tokens + abilities — DONE (2026-07-07), commits `446708fc` (ownership) + `1cfff1b5`
  > (abilities).** Design decisions (user-approved): **system-actor singleton** ownership, and do
  > **application tokens + abilities first** (user PATs deferred).
  > - **Ownership:** panel-wide tokens now belong to a single user-independent `SystemActor`
  >   (`system_actors` table, one seeded row; `HasApiTokens` so Sanctum attaches the access token), so
  >   they survive deletion of the minting admin. `personal_access_tokens.created_by` (nullable FK,
  >   nullOnDelete) records the minter for audit. `CreateApplicationTokenService` mints them.
  >   `AdminAuthenticate` authorizes a SystemActor-owned token (being the panel *is* the authorization).
  >   Key finding that de-risked this: **only `TokenController` read `$request->user()`**, so nothing
  >   else on the shared admin surface needed touching. `ApiKeyData` exposes `createdBy` instead of the
  >   tokenable-as-user (also fixed a latent enum→string DTO bug — store was never test-covered).
  > - **Abilities:** `App\Support\Api\TokenAbilities` — resource-scoped read/write vocabulary
  >   (`servers:read`, `nodes:write`, …) over the top-level application resources, plus `*` and
  >   `{resource}:*`; write implies read; unknown resources require `*`. `EnforceTokenAbilities`
  >   middleware on the `/api/application` group 403s a token missing the required ability (sessions
  >   carry no token, so unaffected). Minting accepts an optional validated `abilities[]` (defaults to
  >   `['*']`). Tests: `tests/Feature/Api/{ApplicationTokenTest,TokenAbilitiesTest}.php`. Suite 144;
  >   PHPStan zero; tsc clean.
  >
  > **Still TODO (deferred):**
  > - **User PATs (`ApiKeyType::ACCOUNT`)** — end-users mint tokens scoped to **their own** resources
  >   (their servers), `tokenable` = the user, managed from the account/security area (a client-side
  >   controller + `/api/client` routes + client UI). Not started.
  > - **Frontend token UI** — there is **no** admin token-management screen yet (nothing consumes
  >   `ApiKeyData` in TS). An ability-picker belongs there when it's built.
  > - This supersedes the `getSSOToken` app-key-JWT stopgap (see the SSO item) — a properly-scoped
  >   token is the real answer for plugin/programmatic access.

- **Basic VM control (power actions) on both admin and client sides — DRY — DONE (backend, 2026-07-05).**
  Admin now has power control at parity with the client, from one shared path:
  - **Shared action `App\Services\Servers\SendServerPowerCommand`** — thin wrapper over
    `ProxmoxPowerRepository->setServer($server)->send($command)`. Both controllers delegate here so
    there's one `PowerCommand` vocabulary and one path to Proxmox. (This is the *interactive* power
    path; the deployment-orchestration `SendPowerCommandJob` — which takes a `DeploymentStep` and
    marks build steps — is unchanged and separate.)
  - **Shared request moved** `Http/Requests/Client/Servers/SendPowerCommandRequest` →
    `Http/Requests/Servers/SendPowerCommandRequest`. Its policy authorize (`can('sendPowerCommand',
    $server)`) already serves **both** surfaces because `ServerPolicy::before()` returns true for a
    root admin **or** the server owner — so one request class, authorization differs only by who passes.
  - **Client** `updateState` now delegates to the action (constructor dep swapped
    `ProxmoxPowerRepository` → `SendServerPowerCommand`); behavior identical.
  - **Admin** `ServerController` gained `getState` (GET) + `updateState` (PATCH), routes
    `GET|PATCH /api/admin/servers/{server}/state` (and, via the API unification, the same under
    `/api/application/...`). Admin scoping is the route group's `AdminAuthenticate` (root_admin, any
    server); no per-server policy needed. Both sit inside the `{server}` group's
    `ValidateServerStatusMiddleware` (power/state on a DELETING server is nonsensical → 409).
  - Tests: `tests/Feature/Controllers/Admin/ServerControllerTest.php` (admin powers any server; admin
    reads state; **owner rejected on the admin surface** — proves it's root-admin-scoped, not
    ownership; invalid command → 422) + new client `updateState` coverage in the existing client test
    (owner happy path; non-owner → 404) locking the shared-action refactor. Full suite **110 passed**,
    PHPStan gate **zero**.
  - **Frontend wiring — DONE (2026-07-05).** `features/servers/state/api.ts` is the TanStack Query
    module: `serverStateQueries.detail(uuid)` (GET state, unwrapping the `{data}` envelope) +
    `useUpdateServerState(uuid)` (PATCH mutation, invalidates the state key on success). Both reference
    the **admin** URI off the Wayfinder dictionary (`ServerController.getState['/api/admin/servers/
    {server}/state']`, likewise `updateState`) since the API-unification twin makes these URI-keyed, not
    callable (same caveat as `features/overview`). UI: `components/interfaces/Admin/Server/
    ServerPowerActions.tsx` renders Start/Restart/Shutdown/Kill as a per-row dropdown in the admin
    servers list (`routes/_app/admin/_dashboard/servers.lazy.tsx`) via the shared `actionsColumn`
    helper. Because Radix only mounts `DropdownMenuContent` while open, the per-row state query fires
    **on menu-open**, not as a table-wide poll — buttons gate on the fetched state (start↔stopped,
    others↔running; loading/transitional disables all). Confirm dialog + sonner toasts mirror the client
    `PowerActionsDropdown`. tsc clean, production build green. **Not verified against a live Proxmox
    node** — both endpoints proxy to Proxmox, so real power actions need a node the dev env doesn't have;
    the compile-time contract (Wayfinder route + DTO envelope + generated types) is fully exercised and
    the backend endpoints are covered by feature tests.
  - **Admin server detail page — DONE (2026-07-06).** The list's `/admin/servers/{id}` link is now live.
    Route mirrors the `nodes.$nodeId` pattern: `routes/_app/admin/servers.$serverId.tsx` (AppLayout +
    loader `preloadServer` + title, single **Overview** nav tab for now — room to add Settings/Backups
    later) with `servers.$serverId/index.{tsx,lazy.tsx}`. The Overview
    (`features/servers/components/admin/detail/ServerDetailOverview.tsx`) shows a **Live state** card
    (power/uptime/CPU/memory from the admin `serverStateQueries.detail(uuid)`), a **Specifications** card
    (vCPU/memory/disk/bandwidth), and a **Details** card (node → link to node detail, owner user id, vmid,
    uuid, created, description) off the admin `useServer(id)` query. Header carries a **Power** dropdown
    that reuses the lock-aware `ServerPowerActions`. Added `preloadServer` to `features/servers/admin/api.ts`.
    tsc + vite build green. **Live-state card needs a real Proxmox node to populate** (getState proxies to
    Proxmox) — the dev env has none, so it renders its skeleton there; page structure + DB-backed cards +
    routing + power dropdown are exercised by the build/type contract. Not yet visually verified in-browser.
  - **Power-action locking — DONE (2026-07-06).** Built as designed below, with the lock *inside*
    `SendServerPowerCommand` so admin + client inherit it for free. See the "Power-action locking" item
    further down (marked DONE) for the shape that landed.

- **VLAN support (GitHub #150) — assessment: mechanism sound, node-global default is too coarse.**
  Request: set a Proxmox VLAN tag on a VM's NIC, with a node-level default + per-VM override.
  - **The core mechanism is correct and *already half-built*.** The right Proxmox primitive is a `tag=`
    on the VM's `netX` line against a **VLAN-aware bridge** (e.g. `net0: virtio=…,bridge=vmbr0,tag=100`) —
    *not* per-VLAN `vmbr0.100` sub-interfaces (the reporter's own "Linux VLAN" tangent). `NetworkDeviceData`
    **already models this**: `#[ProxmoxProperty('tag')] public ?int $vlanTag` (1–4094) and `vlanTrunks`
    from the Phase-2 codec work, and it round-trips through `ServerNetworkService::syncNetworkDeviceConfig`.
    So there's no new push mechanism to build — just a place to *store the desired VLAN* and code to
    populate `$vlanTag` on NIC create/sync, plus UI.
  - **Reconsider "default at the node level."** A node commonly serves **multiple** VLANs (the reporter's
    own use case is per-customer/segment isolation), so one global default per node is too coarse. Convoy's
    real L2-segment boundary is the **`network_interfaces`** row (a bridge on a node) — and IPAM already
    binds address blocks → interfaces → nodes. Put the **default VLAN on the network interface** (or
    address block), with a **per-server override** on the NIC config. That satisfies the reporter's "just a
    default" ask *and* generalizes cleanly, versus a node-wide value they'd immediately outgrow.
  - **Also:** requires the bridge to be VLAN-aware in Proxmox (surface/validate this); validate tag ∈
    [1,4094] or null (untagged); apply on create **and** on NIC edit/rebuild (the sync path already carries
    `tag`, so this is mostly free). Net: adequate *pattern* (default + override), wrong *default location* —
    move it off the node onto the interface/block.

- **Power-action locking — DONE (2026-07-06).** User-initiated power actions are now guarded by a
  per-server lock so a user can't spam start/stop/reboot and enqueue conflicting Proxmox tasks.
  - **Lock = pending-record, one atomic cache entry.** `App\Services\Servers\Power\ServerPowerLockService`
    uses `Cache::add` (SETNX-with-TTL, so atomic) keyed `server:{id}:power-action`, whose *value* is the
    pending action (`PendingPowerActionData`: `command` + `requestedAt`). Laravel's cache **lock** only
    tracks owner/expiry with no payload — hence storing the record in the value, per the note below. A
    60s TTL is the safety net that auto-clears a lock if a request dies before releasing.
  - **Lives inside `SendServerPowerCommand`** (the shared action), so both the client and admin
    `updateState` inherit it. `acquire()` runs before the Proxmox call and throws
    `PowerActionInProgressException` (409, code `power_action_in_progress`, `HasErrorCode`) if held; on a
    Proxmox failure the lock is **released** so the user can retry immediately instead of waiting out the
    TTL. On success the lock stays until the TTL expires (the transition window).
  - **Surfaced to the UI.** `getState` on both controllers composes `pendingPowerAction` onto
    `ServerStateData` (nullable field, populated by the controller from the lock — the raw Proxmox status
    doesn't carry it). Admin `ServerPowerActions` + client `PowerActionsDropdown`/`PowerActionsExpanded`
    disable all power buttons while an action is pending (admin also shows a "… in progress" row); the
    409 is the backstop for the race where the UI hasn't yet polled the pending state.
  - Tests: `tests/Unit/Services/Servers/ServerPowerLockServiceTest.php` (acquire/pending/release,
    double-acquire → 409, release-on-failure retriable) + `tests/Feature/Servers/PowerActionLockTest.php`
    (double command → 409 through the admin endpoint, pending surfaced on state, per-server isolation).
    Suite 117 passed; PHPStan 0; tsc + vite build green.
  - **Deferred (not built, on purpose — YAGNI):** the fuller "poll the Proxmox UPID task and clear only
    once the observed VM state confirms completion" loop. v1 relies on the TTL as the lock window, which
    is a correct spam-guard; UPID-based completion tracking is a background-job follow-up (the UPID from
    `send()` is available to anchor it) and observed-state early-clear is racy during the transition
    (a just-issued `start` still reads `stopped`), so it was left out rather than added speculatively.
- **Replace the SSO-token hack: polished URL signing + real OIDC/OAuth SSO.** The current
  `getSSOToken` flow (`UserController::getSSOToken` → `SSOTokenData`, a short-lived app-key-signed JWT
  minted for plugins so an external app like WHMCS can deep-link a user straight into Convoy) is a
  quick-and-dirty stopgap. `SSOTokenData::fromModel()` and the dropped `sso_tokens` table are already
  gone; the JWT mint path still exists. Two distinct deliverables, don't conflate them:
  1. **A first-class signed-URL option** for the lightweight plugin case (WHMCS → Convoy auto-login
     links). Prefer Laravel's built-in signed routes (`URL::temporarySignedRoute`, `signed` middleware)
     over a bespoke JWT: expiring, tamper-evident, no custom crypto. Wrap it in a clean, documented,
     scoped API (per-integration signing key, explicit expiry, single-use/nonce, audit log) so plugins
     stop hand-rolling app-key JWTs.
  2. **Full OIDC/OAuth SSO** for external-app integration — the polished long-term path. Decide the
     role explicitly (they differ): Convoy as an **OIDC Relying Party** (users log in via an external
     IdP / "Login with WHMCS/Keycloak/Google") vs. Convoy as an **OAuth2/OIDC Provider** (external apps
     offer "Login with Convoy" and/or call Convoy's API with issued tokens). The WHMCS→Convoy direction
     maps to the RP role; API-token issuance maps to the provider role — may want both.
  **Package guidance (user requirement: high-reputation only, no low-star packages):** for the provider
  role use **Laravel Passport** (official first-party OAuth2 server); for the RP/client role use
  **Laravel Socialite** (official) with a vetted OIDC provider. Passport does not ship full OIDC
  (discovery/`id_token`/userinfo) out of the box, so if strict OIDC provider conformance is required,
  vet the OIDC-on-Passport bridge for maintenance/reputation before adopting — do NOT pull a low-star
  package. Confirm the exact integration surface WHMCS (and other plugins) expect before picking.
- **Logged-in session tracking with Redis sessions.** Laravel's database session listing is not available
  when Redis is the session driver, so add first-party session metadata tracking. On login/request,
  upsert a user-session record keyed by session ID/hash with `user_id`, last-used timestamp, IP-derived
  location, user-agent/device/browser/OS summary, and current-session marker. On logout/session revoke,
  delete both the metadata row and the Redis session key for that individual session. Expose an account
  page/API so users can see last used date, basic device info, approximate location, and revoke sessions
  one at a time.

- **Storage accounting polish — PVE truth + reserve buffer (user request 2026-07-07) — DONE
  (2026-07-07): display + reserve + allocation-time enforcement.**

  > **Done (commits `1ffcdc8b` backend + `a82f70cf` UI):** capacity/usage now read from **live
  > Proxmox status** merged into the storage list, cached 15s per node with graceful offline fallback
  > (`online:false`, null physical figures, list still renders). Added `storages.reserved_bytes`
  > (MiB via `StorageSizeCast`) — the reserve-buffer headroom knob. `StorageEloquentData` exposes
  > `physicalTotal/Used/Free`, `committedByConvoy` (server+backup+iso), `untracked`
  > (`physicalUsed − committed`, the base-system slice made explicit), and `freeForConvoy`
  > (`physicalFree − reserved`). `ShowStorageModal` renders the breakdown against physical capacity
  > with distinct "Untracked" + "Reserved headroom" segments; create/edit forms gained the reserve
  > field. Tests: `tests/Feature/Controllers/Admin/Nodes/StorageControllerTest.php` (live merge +
  > derivations; offline fallback). Suite 146; PHPStan zero; tsc + vite build green. **Not visually
  > verified in-browser** — the live/online path needs a real Proxmox node the dev env lacks (offline
  > path + data contract are covered).
  >
  > **Enforcement — DONE (commit `1f3cb9bb`).** `HasSufficientDiskSpace` (already wired on
  > `limits.disk` in `StoreServerRequest`) now checks the requested disk against live
  > `freeForConvoy = physicalFree − reservedBytes` instead of the old `size − committed`, so the
  > reserve actually binds and real capacity (incl. base system) is respected. **Fails open when the
  > node is unreachable** (a transient outage must not block creation — mirrors the list's
  > degradation). The live lookup was extracted to `App\Services\Nodes\LiveStorageService` (shared by
  > the controller + the rule, one cache key `node:{id}:live-storages`). Tests:
  > `tests/Unit/Rules/HasSufficientDiskSpaceTest.php` (reject over-line, allow at-line, fail-open
  > offline). Suite 148; PHPStan zero. **Caveat:** checks the single `limits.disk` today; when
  > multi-disk (below) lands, generalize to per-disk against each disk's target storage.

  Original problem (kept for reference):
  Today a storage's capacity is the operator-entered `Storage.size`, and "usage" is Convoy's *own
  bookkeeping* — `Storage::scopeWithUsageSums()` sums `servers.disk` + `backups.size` + `isos.size`
  (MiB→bytes). That's **what Convoy allocated**, not what the disk actually holds. The base Proxmox
  system, non-Convoy VMs, and filesystem/thin metadata are invisible — they're the gap between PVE's
  real `used` and Convoy's summed `used` — so "X of Y used" undercounts consumption and overpromises
  free space, causing oversubscription / allocation failures. (This is the exact concern the user
  raised: the operator types a total that ignores base-system overhead.)

  **The ground truth already exists but is disconnected:** `ProxmoxStorageRepository::getStorages()` /
  `getStorage($name)` return live `used`/`avail`/`total` per storage as `StorageData` (surfaced by the
  `fetchFromProxmox` endpoint), which *does* include the base system. Nothing reconciles it against
  the `Storage` model's manual `size` + Convoy-committed sum.

  **Design decision (user-approved 2026-07-07): PVE live status is the source of truth for
  total/used/free; add a per-storage _reserve buffer_ (headroom) on top. NO soft cap** — Convoy is the
  only writer to these storages, so a "carve Convoy's slice of a shared storage" cap isn't needed.
  - **Capacity/usage** come from live PVE (`StorageData.total/used/free`), not from `Storage.size`.
    The operator-entered `size` is demoted to advisory (or dropped) — it is no longer the denominator.
  - **Reserve buffer:** new nullable `reserved_bytes` (stored via `StorageSizeCast`, MiB like the
    others) on `storages`. Meaning: never let Convoy allocate into the last N bytes of *free* space.
  - **Fit / allocation check** (drives `TemplateFitsStorage`, server-create disk validation, and the
    admin storage UI's "free to allocate"):
    ```
    free_for_convoy = PVE.avail − reserved_bytes
    ```
    A new disk is allowed only if its size ≤ `free_for_convoy`. Today `TemplateFitsStorage` only checks
    template-vs-server-disk-limit, never against remaining storage capacity — this adds the real check.
  - **Present three distinct figures** in the admin storage view so overhead is *visible*, not
    silently netted out:
    1. **Physical (PVE):** total / used / avail — real, includes base system.
    2. **Allocated by Convoy:** the existing `withUsageSums` figure (server disks + backups + isos).
    3. **Untracked = PVE.used − Convoy-committed** — the base-system + non-Convoy slice, made explicit.
  - **Caching + offline fallback:** live PVE status is a per-node network call — cache with a short TTL
    (mirror `OverviewService`'s 15s) and degrade gracefully when the node is offline: fall back to the
    last-known/`size` figure and flag it stale rather than erroring the whole storage list.
  - **Thin-provisioning note (don't try to reconcile the two to one number):** on thin LVM / ZFS /
    qcow2 the Convoy-committed sum *overcounts* (disks aren't fully written) while PVE `used`
    *undercounts* the commitment — the two legitimately differ. Show both; the point is transparency,
    not forcing them equal.
  - **Coupling with multi-disk (below):** once a server has N disks across storages, the
    "Allocated by Convoy" per-storage sum must aggregate `server_disks`, not `servers.disk`. Build the
    aggregation disk-oriented from the start (or land multi-disk first) so it doesn't need reworking.
  - Touch points: `storages` migration (`reserved_bytes`), `Storage` model + `StorageEloquentData`
    (expose reserve + the live/committed/untracked figures), `StorageController::index` (merge live
    `StorageData` into the eloquent list, cached), `TemplateFitsStorage` (real capacity check),
    `features/nodes/storages` UI (three-figure display + reserve field).

- **Multiple storages (disks) per VM (user request 2026-07-07) — DESIGN APPROVED; SLICES 1–2 DONE
  (2026-07-07). Next: slice 3 (creation accepts `limits.disks[]`).**

  > **Slice 1 (server_disks model + backfill + disk-oriented usage) — DONE.** `server_disks` table
  > (`server_id` cascade, `storage_id`, `size` MiB, `interface` nullable, `is_primary`, `disk_index`;
  > `unique(server_id, interface)`), backfilled with a primary row per existing server from
  > `servers.(storage_id, disk)`. `ServerDisk` model + factory; `Server::disks()`/`primaryDisk()`,
  > `Storage::serverDisks()`. **Expand-first held:** `servers.storage_id/disk` kept authoritative;
  > `ServerCreationService` now also writes the primary `server_disks` row. `Storage::scopeWithUsageSums`
  > + `getServerUsageAttribute` repointed from `servers.disk` to `server_disks.size`, so item 1's
  > `committedByConvoy` is now disk-oriented (counts a server's disks per-storage). `OverviewService`'s
  > per-node `SUM(disk)` still reads `servers.disk` (unaffected). Tests:
  > `tests/Unit/Models/StorageUsageAggregationTest.php` (per-storage + cross-storage sums; relations).
  > Suite 151; PHPStan zero.
  >
  > **Discovered + FIXED (commit `c9714682`):** `ServerCreationService::handle` called
  > `Server::create(['uuid' => …, 'uuid_short' => …])`, but both are `$guarded`, so mass-assignment
  > silently dropped them → `null value in column "uuid"`; server creation via the service was **broken
  > on `next`**. Switched to `Server::forceCreate` (assigns while unguarded; save-time validation
  > unchanged). No creation test had exercised it — now `tests/Feature/Servers/ServerCreationDiskTest.php`
  > locks uuid population + the primary `server_disks` mirror.


  A `Server` today has a **single** `storage_id` + single `disk` (see `Server::storage()` and the
  `disk`/`storage_id` columns). Proxmox VMs support **many** disks (`scsi0..N`, `virtio0..N`, …), each
  potentially on a **different** storage. `DiskData` already parses this (`volume` = `{storage}:...`);
  it's the *panel data model* that flattens to one.

  **Proxmox primitives (verified against `docs/pve-api/`):**
  - **Allocate a fresh empty disk:** config set `scsiN=STORAGE:SIZE_GiB[,opts]` (the special
    `STORAGE_ID:SIZE_IN_GiB` syntax makes PVE allocate a new volume). Fast — metadata only, no copy —
    so it's a **synchronous** `ProxmoxConfigRepository::update` write, no async task tracking.
  - **Resize (grow only):** `PUT …/resize` — already wrapped by `ProxmoxDiskRepository::setDiskSize`.
  - **Move to another storage:** `POST …/move_disk` — **async** (UPID), slow (data copy). Only needed
    to split a *template's own* disks across storages → **deferred (YAGNI)**.
  - Delete: config `delete=scsiN` (already used by `unmountIso`). Boot order: `setBootOrder` (exists).

  **Core insight — two kinds of disk.** The build **clones a template**, so the clone's disks are the
  template's, on the clone's single target storage. Therefore:
  1. **Primary/OS disk** — from the clone, then resized. Storage = clone target (`server->storage->name`,
     already operator-chosen). This *is* today's single `(storage_id, disk)`.
  2. **Secondary/data disks** — not in the template; **allocated fresh** post-clone via
     `scsiN=STORAGE:SIZE`, each on its own target storage.
  ⇒ **v1 never needs `move_disk`.** Primary goes where the clone puts it; secondaries are allocated
  directly on their target.

  **Build sequence (extends the existing job chain, no new async machinery):**
  `BuildServerJob` (clone → primary storage, unchanged) → `WaitUntilVmIsCreatedJob` (unchanged) →
  `ConfigureVmJob`: resize primary (unchanged) → **for each secondary: `config.update({scsiN:
  "storage:SIZE,opts"}, digest)` (NEW)** → set boot order (primary first).

  **Data model — `server_disks`** (one row per disk): `server_id` (FK cascade), `storage_id` (FK),
  `size` (MiB, `StorageSizeCast`), `interface` (nullable string e.g. `scsi1`, assigned at build),
  `is_primary` (bool), `disk_index` (int). `interface` is populated **post-build** (the primary's
  interface isn't known until the cloned config is read; secondaries get the slot we allocate) — makes
  retries deterministic + enables edit/boot-order. Slot assignment reuses the **already-present**
  `DiskInterface::getNextAvailableSlot('scsi', $usedSlots)` (same pattern `mountIso` uses for `ide`).

  **Idempotency/safety (existing Phase-2 patterns):** digest optimistic concurrency on every allocate
  write; **skip-if-present** (read config; if `scsiN` already holds the expected volume, skip — so a
  `ConfigureVmJob` retry, `tries=3`, can't double-allocate); persist the assigned `interface` back to
  the row. Allocation syntax is integer GiB → keep secondary sizes GiB-aligned, allocate exactly.

  **Ties to item 1:** generalize `HasSufficientDiskSpace` from the single `limits.disk` to **per-disk
  against each disk's own storage** (sum disks targeting the same storage) vs `freeForConvoy`. And
  `Storage::scopeWithUsageSums`' server-disk sum moves from `servers.disk` to `server_disks.size` →
  item 1's `committedByConvoy` follows automatically.

  **Decisions (user-approved 2026-07-07):**
  - **Migration shape: expand-first, contract-later.** v1 **keeps** `servers.storage_id`/`disk` as the
    primary-disk pointer (the clone still reads `server->storage->name`); `server_disks` is additive
    with a primary row + secondary rows. A **later** slice turns the columns into `primaryDisk()`
    accessors and drops them. Lower blast radius per slice.
  - **v1 scope INCLUDES post-creation add/remove disks** (not creation-only): add a disk (config
    allocate), remove a disk (config `delete=scsiN` + purge unused), resize a secondary, on an existing
    server — with the server-status/lock guards the other mutation paths use.

  **Slicing:**
  1. ~~**`server_disks` model + backfill** (expand-only). Repoint `scopeWithUsageSums`.~~ **DONE**
     (commit `4e9eee97`, see the Slice-1 note above).
  2. ~~**Secondary-disk allocation**~~ **DONE** (commit `b7a72c03`). `AllocationService::syncDisks`
     allocates each non-primary `server_disks` row as a fresh volume via the `STORAGE:SIZE_GiB` config
     syntax on the next free scsi slot; wired into `VmSyncService` (runs in `ConfigureVmJob` after the
     primary resize). Idempotent/retry-safe (interface persisted *before* the write so a retry reuses
     the slot; skip-if-present; one digest-guarded batched write; no-op when nothing pending). Tests in
     `tests/Unit/Services/Servers/AllocationDiskSyncTest.php`. **No producer of secondary rows yet —
     that's slice 3.** Suite 157; PHPStan zero.
  3. **Creation accepts `limits.disks[]`** + per-disk capacity validation (generalize
     `HasSufficientDiskSpace`, `StorageAllows(KVM)` per disk).
  4. **Post-creation add/remove/resize** endpoints + status/lock guards.
  5. **Frontend** disk list (create form + a server-settings Disks panel).
  6. *(Deferred, YAGNI)* `move_disk`-based splitting of a template's own multiple disks.

## Test database isolation (RefreshDatabase + dedicated `db_test`)

Tests previously ran with `DatabaseTransactions` against the **dev** database (`db`).
Two problems: Postgres sequences are non-transactional, so every rolled-back factory
insert still burned an autoincrement value permanently — and since the dev DB is never
reset, those climbed forever. Fixed by:

- **`RefreshDatabase`** (see `tests/Pest.php`) — `migrate:fresh` at the start of each run
  resets all sequences, so IDs stay small and deterministic per run.
- **A dedicated `db_test` database** so `migrate:fresh` never touches dev data. Created by a
  ddev `post-start` hook (`.ddev/config.yaml`, idempotent `createdb`), owned by `db`.
- **`tests/bootstrap.php`** redirects the suite onto `db_test` when `DB_TEST_DATABASE` is set
  (ddev exports it via `web_environment`). This is done in a bootstrap file, *not* phpunit
  `<env>`, for a specific reason: ddev exports `DB_DATABASE` into the container env, which PHP
  mirrors into **`$_SERVER`**, and Laravel's `Env` reads `$_SERVER` *before* `$_ENV`/`getenv`.
  PHPUnit's `<env force="true">` only rewrites `$_ENV`+`putenv`, so it silently loses — the
  bootstrap overwrites `$_SERVER` too, before the framework boots.
- **CI is untouched**: it doesn't set `DB_TEST_DATABASE`, so the redirect no-ops and tests run
  against CI's throwaway MySQL `convoy` DB (`.github/workflows/tests.yml`). Note CI is still on
  MySQL 8.0 — a Postgres CI service is a Phase 0/3 follow-up.

Verified: after a run, `db_test.locations_id_seq` advances (tests wrote there) while
`db.locations_id_seq` stays at 1 (dev DB untouched).

> ⚠️ During setup, before `db_test` redirection was wired, one `RefreshDatabase` run did a
> `migrate:fresh` against dev `db` and wiped it. Recover from the `postgres-baseline` ddev
> snapshot (`ddev snapshot restore postgres-baseline`) or reseed. Sequence-reset behavior means
> this can't recur now that the redirect is in place.

## Resolved flakiness

- `LocationFactory` now emits deterministic sequential `short_code` values instead of relying on
  Faker's random unique pool, avoiding intermittent unique-index clashes during repeated test runs.
  Verified with `ddev artisan test tests/Feature/Controllers/Admin/LocationControllerTest.php --compact`.

## Static analysis status

- **PHPStan is at ZERO errors** (level 5, `app/`) as of 2026-07-05 — down from the 247-error baseline.
  `ddev exec ./vendor/bin/phpstan analyse --memory-limit=4G` is green. Keep it green.
- Getting the last 18 to zero surfaced (and fixed) several real runtime bugs, not just annotations:
  - **`EloquentRepository`** — model type is now the project base `App\Models\Model` (not Illuminate's),
    and `getBuilder(): Builder<Model>` is generic, so larastan resolves `skipValidation()`/`getValidator()`
    and `newModelInstance()`/`firstOrFail()` return the project model. `count()` no longer passes columns;
    `updateOrCreate` uses `getKey()` not `->id`.
  - **`ServerDeletionService`** — was BROKEN at runtime. `validateStatus()` compared the `ServerStatus`
    enum to a string (`ServerStatus::DELETING->value`), so `!== 'deleting'` was always true → every admin
    server deletion threw `ServerStatusConflictException`. Also its non-`noPurge` branch was a broken
    duplicate of `DeleteServerAction::execute()` (spread of a `void`, passed `$server->id`/`$server` where
    a `Deployment` is expected). Rewritten to create a `DeploymentType::DELETE` deployment and delegate to
    `DeleteServerAction::execute($deployment)`; the `noPurge` fast-path (`$server->delete()`) is preserved.
    Enum comparison fixed to the case. `verifyStatusOnly` is still dead (only caller passes default).
  - **Passkey actions** — were BROKEN at runtime under `web-auth/webauthn-lib ^5` (5.3+). The validators'
    `check()` renamed the arg to `credentialRecord` and now return a base `CredentialRecord`, but
    `Passkey::data`'s setter requires a `PublicKeyCredentialSource` → registration/auth would TypeError.
    Both `StorePasskeyAction` and `FindPasskeyToAuthenticateAction` now wrap the result with
    `PublicKeyCredentialSource::fromCredentialRecord(...)` (the lib's sanctioned bridge). Added
    `@property PublicKeyCredentialSource $data` to the `Passkey` model. NOTE: `PublicKeyCredentialSource`
    is `@deprecated` since 5.3 and removed in webauthn-lib 6.0 — a pre-6.0 follow-up is to migrate the
    `Passkey` model + `PasskeySerializer` to store the base `CredentialRecord` type.
  - **Dead code removed:** `App\Data\Auth\SSOTokenData::fromModel()` (referenced `App\Models\SSOToken`,
    whose table was dropped in 2023; the only live use constructs the DTO directly) and
    `App\Extensions\Spatie\Fractal\RecursiveSerializer` (extended `league/fractal`, which isn't installed —
    a leftover from the pre-laravel-data transformer stack; referenced nowhere).
- `ddev artisan test --compact` → 83 passed (198 assertions) after these changes.
