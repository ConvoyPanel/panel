# v10 (`next`) rewrite — working handoff

Living notes for the effort to ship `next` (v10) as the new trunk. Update as phases land.
Roadmap of record: `~/.claude/plans/help-me-plan-a-cryptic-peacock.md` (5 phases). This file
tracks *what's actually done* and *what to pick up next*, so a cold start doesn't re-derive it.

Last updated: 2026-07-04

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
- **Phase 2 (Proxmox config-push safety)** — IN PROGRESS. See below.

**Test suite:** `ddev artisan test --compact` → 43 passed (68 assertions) as of this writing.

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

### Exception refactor (JUST COMPLETED — commit pending in this session)
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

> Note: `unmountIso`'s disk lookup uses `->where('media_name', ...)` on a `Collection<DiskData>`, but
> `DiskData` has no `media_name` property — a **pre-existing** bug that makes the found-branch
> unreachable (so it currently always throws `IsoAlreadyUnmountedException`). Left as-is; worth a
> separate fix. The digest change is correct for when the disk *is* found.

## Next up (rest of Phase 2, then Phase 3)

- **`syncNetworkDeviceConfig`** should filter already-firewalled NICs (avoid redundant writes).
- **Golden-master round-trip tests** on real PVE config fixtures: assert
  `fromRaw(x) → toProxmoxString → fromRaw` drops nothing. This is the Phase-2 safety net.
- **Phase 3 (prod migration)** is the hard requirement after Phase 2: cross-engine
  MySQL 8.0 → Postgres 17 cutover (pgloader) on top of 24 breaking rename migrations; dry-run
  against a restored prod snapshot; reconcile `develop`'s newer commits.

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

## Known flakiness
- `LocationFactory` auto-generated `short_code` can clash → intermittent test failures. Make it
  collision-proof (e.g. sequence/unique faker) when convenient.
