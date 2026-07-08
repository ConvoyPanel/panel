# v10 (`next`) rewrite — working handoff

Living notes for shipping `next` (v10) as the new trunk. Roadmap of record:
[v10-roadmap.md](v10-roadmap.md). This file tracks *what's done* (one-line pointers — git history
holds the detail) and *what to pick up next*, so a cold start doesn't re-derive it.

Last updated: 2026-07-08

---

## Where we are

Phases **0, 1, 2, 4** are DONE. Phase 3 tooling is a shipped operator deliverable. v10 is
**Postgres-only** (decision 2026-07-07; CI on Postgres 17). The remaining work is the **open product
follow-ups** below — all doable in-sandbox, no prod data.

- **Phase 0 (dev loop / CI green)** — DONE. ddev runs the full stack on Postgres 17 (nginx-fpm, Redis
  cache/queue/session, Horizon + `schedule:work`, codegen via `ddev npm run build`). Vite HMR points at
  the ddev host; README documents the `ddev start → composer install → artisan migrate → npm install/build`
  bootstrap.
- **Phase 1 (backend green)** — DONE. Orphaned routes reconciled, stale pool-era filters removed, dozens
  of real bugs fixed (route binding uuid/bigint on Postgres, backup semantics, storage refactor, etc.).
- **Phase 2 (Proxmox config-push safety)** — DONE. Config digest optimistic concurrency threaded through
  every read-modify-write path; property-list DTO codec landed; redundant NIC/Configure writes filtered;
  golden-master round-trip safety net in place. Detail in **Design constraints** below.
- **Phase 3 (operator cutover)** — tooling DONE under `database/cutover/` (RUNBOOK.md, pgloader recipe
  `v4-to-v10.load`, `verify.sh`). This is what *downstream operators* run to upgrade a v4 MySQL install to
  v10 Postgres; the maintainer has no prod data, so there is **no maintainer-side dry-run pending**. Fresh
  v10 installs start on Postgres and skip it. `verify.sh` caveat: pgloader is amd64-only and segfaults under
  arm64-Linux qemu (works on x86_64 or macOS Docker/Rosetta); takes a `PGLOADER_IMAGE` override.
- **Phase 4 (frontend data layer)** — DONE. Old `api/` directory gone; zero raw `axios` calls; every
  domain on a `features/<domain>/api.ts` module (`queryOptions` + `apiFetch` + Wayfinder routes). SWR shim
  removed. Reference impls: `features/servers/api.ts`, `features/overview/api.ts`.

### Shipped feature work (all on `next`, tests green)
- **IPAM allocator rewrite** — all 4 slices done. Allocation is one indexed `FOR UPDATE SKIP LOCKED`
  query (O(log N + n), race-free); IPs stored as Postgres `inet` with a partial index; sparse
  materialization for large/v6 blocks (no address-space-sized generation); `AddressState` enum
  (available/assigned/reserved) + reserve API + auto-reserved network/broadcast/gateway. Commits
  `20c6de73`/`46d9b398`/`eb455c95`/`75ada6ec`/`6a5cac43`.
- **API tokens v2 — COMPLETE (application + account halves + both UIs).** Application half: SystemActor
  singleton ownership (survives minter deletion), resource-scoped abilities (`servers:read`, `nodes:write`,
  `*`) enforced by `EnforceTokenAbilities` on `/api/application` (`446708fc`/`1cfff1b5`). **User PATs
  (`ApiKeyType::ACCOUNT`)**: `CreateAccountTokenService` mints a token owned by the user; the client API
  (`/api/client`) now accepts both the web session and Sanctum bearer tokens (`auth:web,sanctum`, web-first
  so session requests carry no access token and are never ability-scoped). `AccountTokenAbilities`
  (`servers:read/write`) is enforced via the now-vocabulary-parameterized `EnforceTokenAbilities`; the whole
  `/account` group is `DenyApiTokenAccess` (a token can never change its own account or mint/revoke tokens).
  A custom `ValidateCsrfToken` exempts *valid* bearer requests (browsers can't forge them) while keeping full
  CSRF for the SPA. **Both UIs shipped**: user PATs in the account security page (`features/account/api-keys`,
  scope picker + one-time reveal), admin app tokens at `/admin/tokens` (`features/tokens`, DataTable + full
  resource ability picker). Frontend build/type-verified only (no in-browser click-through).
- **VM power actions (admin + client)** — shared `SendServerPowerCommand` action, admin `getState`/`updateState`
  routes, per-server power lock (`ServerPowerLockService`, `Cache::add` SETNX+TTL, 409
  `power_action_in_progress`), pending action surfaced on `ServerStateData`. Admin server detail page +
  power dropdown. Backend **live-verified** (below); frontend build/type-verified only.
- **Storage accounting** — capacity/usage read from **live PVE status** (cached 15s, graceful offline
  fallback), `storages.reserved_bytes` headroom knob, `StorageEloquentData` exposes
  physical/committed/untracked/`freeForConvoy`. `HasSufficientDiskSpace` enforces requested disk ≤
  `freeForConvoy` (fails open when node unreachable). Live-verified.
- **Multiple disks per VM** — SLICES 1–5 DONE, feature-complete for v1 (slice 6 `move_disk` splitting is
  deferred/YAGNI). `server_disks` table (expand-first: `servers.storage_id/disk` kept as primary pointer),
  disk-oriented usage sums, secondary-disk allocation in the build chain, creation `limits.disks[]` +
  per-storage capacity aggregate, post-creation add/resize/remove endpoints (admin, `{server}` group),
  frontend create-form disk array + admin "Disks" tab. Live-verified.

### Live verification against a real Proxmox node (2026-07-08, PVE 9.2.2 `us-southeast-2`)
Drove the service/repo→PVE path against the seeded `DevNodeSeeder` node over `PROXMOX_SSH_TARGET`,
cross-checked with `qm`/`pvesh` over SSH:
- **Power:** `getState`, `start`→running, `kill`→stopped.
- **Storage:** live figures byte-for-byte match `pvesh get /nodes/.../storage`; all
  `StorageEloquentData` derivations correct; `freeForConvoy` reserve binds; enforcement accept/reject +
  per-storage aggregate + offline fail-open all confirmed.
- **Disks:** `addDisk`→`scsiN` allocated, `resizeDisk`→grown, `removeDisk`→detached **and backing volume
  destroyed on storage**.
- **Bugs found & fixed live:** `removeDisk` orphaned-volume race (`d25bf56b` — poll raw config for the
  `unusedN` before destroying, because a running VM's `delete=scsiN` schedules the unplug async);
  `DevNodeSeeder` hardcoded `name=dev-node` broke every `/nodes/{name}` call (now derived from the fqdn's
  first DNS label, override `PROXMOX_NODE_NAME`).
- **Still unproven:** in-browser render of all frontend surfaces (overview dashboard, storage modal,
  power actions, disks tab) — no browser driver in-sandbox.

---

## Next up — open product follow-ups

Researched direction retained for each; none built unless noted.

- **User PATs + token UIs — DONE.** See "API tokens v2" above (account tokens on `auth:web,sanctum`,
  both the client PAT card and the admin `/admin/tokens` screen shipped).

- **SSH keychain + server auth — DONE.** Keychain: `Client\Account\SSHKeyController` (index/store/destroy)
  on the existing `ssh_keys` table, `SshPublicKey` rule (base64 blob + embedded-algorithm integrity check),
  real `KeychainCard` (was mocked). Server auth: the declared-but-missing `/servers/{uuid}/security` tab now
  exists (`features/servers/security`), driving the existing `SettingsController` auth endpoints —
  `SSHKeysCard` edits a server's cloud-init authorized-key set (add from keychain / paste one-off / remove /
  save the full set) and `PasswordCard` sets the root password. **One-off keys** work as proposed: a one-off
  is simply a key in the server's set with no keychain row — no new table/flag. *Other declared-but-unbuilt
  server tabs remain: `iso-library`, `storage`, `networking` (nav points at them; no route files yet).*

- **Logged-in session tracking — DONE.** `session_records` table + `RecordSessionActivity` middleware
  (web group, throttled, skips bearer/anon) + Logout listener + `SessionRecordController` (list/revoke via
  the Redis session handler); real `SessionListCard`. Raw `session_id` is server-side only; API exposes the
  numeric id. **Note:** device label is UA-parsed client-side; **IP→geo location was dropped** (needs a
  GeoIP dependency) — add later if wanted.

- **VLAN support (GitHub #150) — ASSESSED, NOT BUILT.** Mechanism is sound and half-built: the right PVE
  primitive is `tag=` on the VM's `netX` against a VLAN-aware bridge, which `NetworkDeviceData` already
  models (`#[ProxmoxProperty('tag')] public ?int $vlanTag`, round-trips through
  `ServerNetworkService::syncNetworkDeviceConfig`). So there's no new push mechanism — just somewhere to
  store the desired VLAN + populate `$vlanTag` on NIC create/sync + UI. **Key call:** node-global default
  is too coarse (a node serves many VLANs); put the **default on the `network_interfaces` row** (the real
  L2 boundary, already bound to address blocks) with a **per-server override** on the NIC. Validate tag ∈
  [1,4094] or null; require the bridge be VLAN-aware; apply on create *and* NIC edit.

- **Replace the SSO-token hack — NOT BUILT.** Current `UserController::getSSOToken` mints a short-lived
  app-key-signed JWT so plugins (e.g. WHMCS) can deep-link a user into Convoy — a stopgap. Two distinct
  deliverables, don't conflate: (1) a **first-class signed-URL** option for the lightweight plugin case —
  prefer Laravel `URL::temporarySignedRoute` + `signed` middleware over bespoke JWT, wrapped in a scoped
  API (per-integration key, expiry, single-use nonce, audit log); (2) **full OIDC/OAuth SSO** — decide the
  role (Convoy as OIDC **Relying Party** for "login via external IdP" vs. OAuth2/OIDC **Provider** for
  "login with Convoy" / API tokens); WHMCS→Convoy is the RP direction. **Package rule (user req: high-rep
  only):** provider role → Laravel **Passport**; RP role → Laravel **Socialite**. Passport lacks full OIDC
  out of the box — vet any OIDC-on-Passport bridge for reputation before adopting. Supersedes the
  app-key-JWT stopgap once done.

- **Multiple disks per VM — slice 6 (`move_disk` splitting) — DEFERRED (YAGNI).** Only needed to split a
  *template's own* multiple disks across storages; async (UPID), slow. v1 never needs it (primary comes
  from the clone; secondaries are allocated fresh on their target storage).

- **In-browser visual verification.** Several frontend surfaces are build/type-verified but never
  clicked live (see live-verification note above). Needs a browser driver + a real node.

---

## Gotchas / must-know (still live)

- **Run tests with `ddev exec vendor/bin/pest` (or `ddev exec php artisan test`), NOT `ddev artisan
  test`.** The ddev global-command wrapper segfaults (exit 139) booting the test runner in this sandbox —
  `ddev artisan tinker`/`migrate` are fine, so it's a wrapper quirk, not a regression. Last green:
  **171 passed** on `next`.
- **Test DB isolation.** Suite uses `RefreshDatabase` (`migrate:fresh` resets Postgres sequences) against
  a dedicated **`db_test`** DB (ddev `post-start` `createdb`, idempotent). `tests/bootstrap.php` redirects
  onto `db_test` when `DB_TEST_DATABASE` is set — done in bootstrap (not phpunit `<env>`) because ddev
  exports `DB_DATABASE` into `$_SERVER`, which Laravel's `Env` reads before `$_ENV`/`getenv`. CI is
  untouched (doesn't set the var; runs against its throwaway MySQL DB — a Postgres CI service is a
  follow-up). ⚠️ Never point `RefreshDatabase` at dev `db` — it will wipe it; recover via
  `ddev snapshot restore postgres-baseline`.
- **PHPStan is at ZERO** (level 5, `app/`). `ddev exec ./vendor/bin/phpstan analyse --memory-limit=4G` is
  green — keep it green. (Getting there fixed real runtime bugs: `ServerDeletionService` enum-vs-string
  comparison broke every admin deletion; passkey actions TypErrored under webauthn-lib ^5 — see git
  history if these resurface. `PublicKeyCredentialSource` is `@deprecated` since 5.3 / removed in 6.0, so a
  pre-6.0 follow-up is migrating `Passkey`/`PasskeySerializer` to the base `CredentialRecord` type.)
- **Wayfinder emits URI-keyed dictionaries for admin controllers.** `routes/api-admin.php` is served under
  **two** prefixes — `/api/admin` (web session, `['auth', AdminAuthenticate]`) and `/api/application`
  (Sanctum Bearer, `['auth:sanctum', AdminAuthenticate]`) — so every admin action has two routes and
  Wayfinder emits `{ '/api/admin/…': fn, '/api/application/…': fn }` instead of a callable. **Reference the
  admin URI explicitly** (e.g. `OverviewController['/api/admin/overview']()`; see `features/overview/api.ts`,
  `features/locations/api.ts`). Client controllers (`/api/client/*`, `/api/auth/*`) have no app-API twin
  and stay clean callables — prefer migrating/adding those without the twin. `DenyApiTokenAccess` gates
  `/tokens` (a token must never mint/revoke tokens); `EnforceTokenAbilities` gates the rest.
- **PVE read-after-write staleness.** An immediate config GET in the *same process* right after a config
  write can miss the change (observed live: write persisted + task `OK`, but a 100ms-later re-read returned
  the pre-write config). Doesn't affect one-op-per-request endpoints or the build chain (never re-reads
  disk config after allocating). For any future same-request read-modify-write that reads back its own
  just-written change, **poll** (as `removeDisk` now does) rather than a single re-read.

---

## Design constraints to preserve

- **Exceptions: explicit error codes, never a classname fallback.** Legacy `DisplayException` is gone;
  18 subclasses now `extends <Symfony HttpException> implements HasErrorCode` with hand-written slugs.
  Rules (from the user): client always gets a real HTTP status (never a generic 500); messages are curated
  and never attach `$previous` where it could leak internals; **no auto-derived codes** (that's what leaks
  internals in private forks). The render hook lives in `bootstrap/app.php`, fires only for
  `$request->expectsJson()`, emits `{ message, code }`. `ConvoyException` kept as base for
  `RepositoryException`/`DataValidationException`/`InvalidJWTException`.
- **Proxmox property-list DTO codec** (`app/Extensions/Spatie/Data/Proxmox/`). Attribute-driven codec for
  PVE's `head[,key=value]*` format, replacing hand-written parse ladders. `#[ProxmoxProperty('key', cast)]`
  declares each property; `MapsProxmoxProperties` trait does reflection; `extraProperties` preserves
  unmodeled sub-keys losslessly on round-trip. **The PVE keys are a *second* serialization target** —
  laravel-data's `MapOutputName` is already the frontend/TS JSON contract, so PVE keys get a dedicated
  attribute, not reused `MapName`. Byte scaling is centralized in `App\Support\ByteUnit` (1024-based).
  Applied to: `NetworkDeviceData` (reference, emits), `TpmStateDiskData` (emits), `UsbDeviceData`
  (parse-only), `DiskData` (partial, parse-only). **Do NOT add `toProxmoxString`/emit speculatively** —
  disk/USB re-emit is a real VM-write risk that belongs with a tested push path. `CloudinitConfigData`
  and `VgaConfigData` don't fit the codec (not `head[,key=value]*` strings).
- **Config digest optimistic concurrency** only helps *read-modify-write* (payload/key-set derived from a
  just-read config). Blind writes whose values come from args (not the read config) are **deliberately
  left without a digest** — threading one there forces an extra `getConfig()` round trip and raises
  spurious 409s. `ConfigModifiedException` (409) surfaces a PVE digest mismatch.
- **Application API = one source of truth.** `routes/api-application.php` was deleted; `routes/api-admin.php`
  serves both guards (the "one file, two entry points" decision). The `/api/application` group gained
  `AdminAuthenticate` (the old group had none — any token could hit admin endpoints). `ApplicationApiTest`
  locks the four guarantees.
