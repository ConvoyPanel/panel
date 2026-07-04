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

**Done:** `NetworkDeviceData` refactored onto it (reference implementation). Golden-master
round-trip tests in `tests/Unit/Data/NetworkDeviceDataTest.php` (5 cases: full-field parse,
full round-trip, PVE bool 1/0 + null omission, unmodeled-key preservation). Suite: 46 passed.
PHPStan clean on the new files (the full-suite 272 errors are the pre-existing `next` baseline,
not from this change).

**TODO — apply the same pattern to the other compound DTOs:**
- `DiskData` — biggest win (huge `data_get(...)` ladder + per-field `filter_var` bools). Note
  the `mbps`/`bps` dual-unit reads and size-unit suffix parsing need bespoke casts.
- `TpmStateDiskData` — also give it the `extraProperties` losslessness it currently lacks.
- Consider `CloudinitConfigData`, `UsbDeviceData`, `VgaConfigData` if they share the format.

## Next up (rest of Phase 2, then Phase 3)

- **Thread digest through the rest of the write paths:** `CloudinitService`, `AllocationService`,
  `ServerAuthService`.
- **`syncNetworkDeviceConfig`** should filter already-firewalled NICs (avoid redundant writes).
- **Golden-master round-trip tests** on real PVE config fixtures: assert
  `fromRaw(x) → toProxmoxString → fromRaw` drops nothing. This is the Phase-2 safety net.
- **Phase 3 (prod migration)** is the hard requirement after Phase 2: cross-engine
  MySQL 8.0 → Postgres 17 cutover (pgloader) on top of 24 breaking rename migrations; dry-run
  against a restored prod snapshot; reconcile `develop`'s newer commits.

## Known flakiness
- `LocationFactory` auto-generated `short_code` can clash → intermittent test failures. Make it
  collision-proof (e.g. sequence/unique faker) when convenient.
