# v10 (`next`) rewrite — working handoff

Living notes for the effort to ship `next` (v10) as the new trunk. Update as phases land.
Roadmap of record: `~/.claude/plans/help-me-plan-a-cryptic-peacock.md` (5 phases). This file
tracks *what's actually done* and *what to pick up next*, so a cold start doesn't re-derive it.

Last updated: 2026-07-05

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

## Next up (Phase 3)

- **Phase 3 (prod migration)** is the hard requirement after Phase 2: cross-engine
  MySQL 8.0 → Postgres 17 cutover (pgloader) on top of 24 breaking rename migrations; dry-run
  against a restored prod snapshot; reconcile `develop`'s newer commits.

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
applied most fixes to *both* branches, so nearly all are already reconciled on `next`. Verified:
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
     - **Discovered en route (unrelated bug, not fixed):** `database/factories/AddressFactory.php` is
       stale — it sets the pre-IPAM-revision columns (`type`, `address`, `cidr`, `gateway`, `mac_address`)
       that no longer exist (the table is now `ip`, `prefix_length`, `address_block_id`, `server_id`), and
       omits the required `address_block_id`. So `Address::factory()` can't create a valid row; seed via
       an explicit `AddressBlock` (see `OverviewControllerTest`). Worth fixing the factory separately.
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

- **Power-action locking.** User-initiated server power actions need an app-level lock so the user
  cannot spam start/stop/reboot buttons and enqueue conflicting Proxmox tasks. Laravel's cache lock
  only stores ownership/expiry, not an arbitrary payload describing the requested action, so model the
  action state separately from the mutex. Suggested shape: acquire a per-server power lock, persist a
  small pending-action record/state (`server_id`, user, requested action, requested_at, Proxmox UPID if
  available), dispatch the command, poll/read server status, and clear or mark failed only after the
  observed VM state confirms the action completed or timed out. UI should render the locked action/status
  and reject duplicate/conflicting requests while the pending action is active.
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
