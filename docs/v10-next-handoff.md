# v10 (`next`) rewrite — working handoff

Living notes for shipping `next` (v10) as the new trunk. Roadmap of record:
[v10-roadmap.md](v10-roadmap.md). This file tracks *what's done* (one-line pointers — git history
holds the detail) and *what to pick up next*, so a cold start doesn't re-derive it.

Last updated: 2026-07-09 (session: **adopted `spatie/laravel-passkeys`** [commit `20d8a442`] — backend-only
swap, package-default `PublicKeyCredentialSource` storage, `user_id` schema kept via a thin model subclass,
curated error-code exceptions + canary/localhost origins preserved via subclasses; deleted the four copied
actions/serializer; full Pest 202 + PHPStan-zero green, live browser WebAuthn ceremony still unproven) — prior: passkey PublicKeyCredentialSource→CredentialRecord migration [interim, now superseded]; corrected stale CI-on-MySQL note [CI already Postgres 17]; recorded node Overview/servers/ipam/settings pages as still-unbuilt design follow-ups) — prior: VLAN committed; visual harness up; FE overhaul re-scoped; Vercel sidebar built+polished; admin grouped nav + avatar workspace switch browser-verified; nav exit transition + global command palette; account security lazy sensitive queries; ddev db collision fixed at the core/portable; seeder env overrides)

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
  is simply a key in the server's set with no keychain row — no new table/flag.

- **Client server tabs — ALL BUILT.** The nav declared five sections with no route files; all now exist:
  `security` (above), `networking` (`features/servers/networking`: addresses table + editable DNS),
  `iso-library` (`features/servers/media`: mount/unmount node ISOs), `storage`
  (`features/servers/storage`: live disk-usage bar + boot-order editor). All drive pre-existing client
  `SettingsController`/`ResourceController`/`AddressController` endpoints — frontend-only, build/type-verified
  (no in-browser click-through). Fixed a latent bug found here: `rawDataToServerResources`
  read `data.usedBytes/totalBytes` but `ResourceController` emits `used_bytes/total_bytes`, so the Overview
  "Storage Usage" card had always shown 0 used — now corrected, and the Storage tab reuses the shared
  `useServerResources` hook.

- **Logged-in session tracking — DONE.** `session_records` table + `RecordSessionActivity` middleware
  (web group, throttled, skips bearer/anon) + Logout listener + `SessionRecordController` (list/revoke via
  the Redis session handler); real `SessionListCard`. Raw `session_id` is server-side only; API exposes the
  numeric id. **Redis↔table desync handled:** `index()` reconciles each row against the session store
  (`getHandler()->read()`) and deletes ghosts whose session expired/was evicted; `SessionRecord` is
  `Prunable` (older than `session.lifetime`) on a daily schedule; the row-missing direction self-heals via
  the middleware. `SessionRevocationService` destroys the store session + row together (used by revoke and
  by a `User::deleting` hook, so deleting a user doesn't leave live Redis sessions). Note
  `SESSION_LIFETIME=525600` (1yr); bulk query-builder user deletes bypass the model event (rows still
  cascade; Redis sessions then TTL-expire). **Note:** device label is UA-parsed client-side;
  **IP→geo location was dropped** (needs a GeoIP dependency) — add later if wanted.

- **VLAN support (GitHub #150) — DONE.** `network_interfaces` now store `is_vlan_aware` plus a nullable
  default `vlan_tag`; `servers` persist the selected primary `network_interface_id` plus a nullable override.
  Validation enforces tag ∈ [1,4094] and blocks tags unless the selected bridge is marked VLAN-aware.
  `ServerNetworkService` emits PVE `tag=` from server override → interface default → no tag, clears stale
  tags when the selected interface has none, and preserves existing PVE tags when no desired interface can be
  inferred. Admin UI exposes VLAN-aware/default fields on node network-interface create/edit/cards and a
  server-create override that is disabled unless the selected interface is VLAN-aware. Focused Pest tests,
  frontend typecheck/build, and PHPStan (`--debug`, serial) are green; no live PVE or in-browser click-through
  for this slice.

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

- **Adopt `spatie/laravel-passkeys` — DONE (commit `20d8a442`, 2026-07-09).** Backend swapped to the
  maintained package (v1.8.1; webauthn-lib stayed ^5.3, no dep churn). What shipped, vs. the plan below:
  - **Schema = option (a).** Kept the `user_id` FK. `App\Models\Passkey extends
    Spatie\LaravelPasskeys\Models\Passkey` and keeps `const UPDATED_AT = null` (our table has no
    `updated_at` — the package model defaults `$timestamps = true`, so **this override is load-bearing**;
    drop it and every passkey save/update SQL-errors), a `user()` belongsTo, `id` route binding (Eloquent
    default — no override needed), and `$validationRules['name']` (still read by `RenamePasskeyRequest`).
    `User implements HasPasskeys` + `getPassKeyName/Id/DisplayName` (id = uuid, preserving the prior
    WebAuthn user-handle). `config/passkeys.php` points `models.*` + `actions.*` at our classes. No data
    or schema migration (the plan's byte-identical claim held; also **no prod passkey rows** anywhere).
  - **Storage = package default** (`PublicKeyCredentialSource` via the package cast + `CredentialRecordConverter`).
    This **supersedes the interim `CredentialRecord` migration** (`47d9607c`) as planned.
  - **Deleted** the four copied classes the package provides
    (`GeneratePasskeyRegisterOptionsAction`/`GeneratePasskeyAuthenticationOptionsAction`/`FindPasskeyToAuthenticateAction`/`PasskeySerializer`);
    controllers inject the package actions directly. **`StorePasskeyAction`** is now a thin subclass of the
    package action — inherits its `execute()` (package-default storage + `PasskeyRegisteredEvent` +
    `additionalProperties`) but overrides the three protected helpers to re-throw our `HasErrorCode`
    exceptions (the package lumps all failures into one generic `InvalidPasskey`, so class-level render
    mapping couldn't preserve our distinct slugs — hence the subclass). **`ConfigureCeremonyStepManagerFactoryAction`**
    subclass folds canary/localhost origin handling into the package's single ceremony hook.
  - **Kept** exactly as the plan required: controllers, routes, `PasskeyData`, the `rename` endpoint,
    `last_used_at`, and the four curated exceptions.
  - **Behavioral deltas from the old hand-rolled path (accepted as package defaults):** register options
    no longer send `excludeCredentials` (double-registration guard dropped); auth options use empty
    `allowCredentials` (discoverable/resident keys) and default userVerification instead of forced
    REQUIRED. `credential_id` is now encoded by the package (`base64` on Postgres) — differs from the old
    URL-safe-unpadded encoding, but immaterial with no existing rows (store + lookup both go through the
    package, so new passkeys are self-consistent).
  - **PHPStan note:** the package's `FindPasskeyToAuthenticateAction::execute(): ?Passkey` returns the
    *base* model type, so `$passkey->user` needs a `/** @var \App\Models\Passkey */` narrow at the two
    controller call sites (config binds the subclass at runtime).
  - **Verification:** full Pest **202 passed**, PHPStan level-5 **zero**, and a wiring smoke (config
    resolves our subclasses, `User` passes the package's `HasPasskeys` guard, register-options generate
    with the right challenge/user fields). **STILL UNPROVEN — the live browser WebAuthn register/login
    ceremony** (attestation/assertion round-trip); needs a CDP virtual authenticator (Playwright), the
    in-sandbox option flagged originally. Tinker was unusable this session (persistent SIGSEGV — the known
    sandbox quirk), so the smoke ran through the test runner instead.

  <details><summary>Original plan (for reference)</summary>

  Replace the
  hand-rolled passkey backend with the maintained Spatie package. **Key finding:** the current code
  (`app/Actions/Auth/*Passkey*Action.php`, `app/Models/Passkey.php`, `app/Services/Auth/PasskeySerializer.php`)
  is already a **hand-derived fork of this exact package** — the four action classes have identical names to
  the package's config-swappable actions (`GeneratePasskeyRegisterOptionsAction`, `StorePasskeyAction`,
  `GeneratePasskeyAuthenticationOptionsAction`, `FindPasskeyToAuthenticateAction`, plus its
  `ConfigureCeremonyStepManagerFactoryAction`), same webauthn-lib calls. So this is a swap of copied classes
  for the dependency, **not a rewrite**. Scope/decisions locked with the maintainer:
  - **Backend-only migration.** The package's UI is **Livewire + Blade** (optional Inertia); this app is a
    React/TanStack SPA on JSON endpoints. So **keep all of** our controllers (`Client\PasskeyController`,
    `Auth\PasskeyLoginController`, the `ConfirmableIdentityController` passkey re-auth path), routes
    (`api-client.php` `/passkeys/*`, `api-auth.php` `/passkeys/*`), the `PasskeyData` DTO, the `rename`
    endpoint, and `last_used_at` tracking. None of that comes from the package.
  - **Storage = package DEFAULT (maintainer's call).** Adopt the package model's `data` cast as-is, which
    stores the (deprecated-in-6.0) `PublicKeyCredentialSource` via its `CredentialRecordConverter`. This
    **supersedes the interim `CredentialRecord` migration** (commit `47d9607c`) — that fix keeps the
    hand-rolled path clean *until* the package lands, then the package's default takes over. JSON is
    byte-identical both ways, so **no data migration** for existing `passkeys.data` rows in either direction.
    (Revisit if Spatie itself moves to `CredentialRecord` before 6.0.)
  - **OPEN decision to settle at build time — schema.** Package defaults to a **polymorphic `authenticatable`
    morph** (`authenticatable_type`/`authenticatable_id`); our table is a plain `user_id` FK
    (`2024_11_14_214143_create_passkeys_table`) with `User::passkeys()` `hasMany`. Two options: (a) keep
    `user_id` by pointing config `models.passkey` at a thin subclass that overrides the relation + keeps the
    FK (least churn, recommended), or (b) migrate to the morph columns + backfill (matches package upstream
    but a data migration on a live table). Also add the `HasPasskeys` interface/trait to `User`.
  - **Bespoke pieces that STAY regardless** (package doesn't provide, and the project's design constraints
    forbid swapping): the curated **error-code exceptions** (`InvalidPasskeyException`, `InvalidPasskeyJson`,
    `InvalidAuthenticatorAttestationResponse`, `InvalidPasskeyPublicKeyCredential` — must keep `HasErrorCode`
    slugs, never a classname fallback), the `canary`/`localhost` origin handling (fold into a subclassed
    `ConfigureCeremonyStepManagerFactoryAction`), and `PasskeyData`.
  - **Concrete steps (build session):** (1) `composer require spatie/laravel-passkeys`; verify its
    webauthn-lib constraint is compatible with our pinned version. (2) Publish + wire `config/passkeys.php`:
    `relying_party.id` = `parse_url(config('app.url'), PHP_URL_HOST)`, point `actions.*` at our subclasses
    where we need custom behavior (ceremony origins), point `models.passkey` per the schema decision. (3)
    Delete the now-duplicated hand-copied actions/serializer where the package's equivalent + a thin subclass
    suffices; rewire the controllers' constructor deps to the package/subclass actions. (4) Keep the routes,
    DTO, exceptions, `rename`, `last_used_at`. (5) Add `HasPasskeys` to `User`. (6) Re-point/keep the
    `tests/Unit/Models/PasskeyDataCastTest.php` coverage (adjust the asserted class to whatever the adopted
    model stores). (7) Full Pest + PHPStan-zero + a real WebAuthn register/login smoke (needs a browser
    authenticator; virtual-authenticator via Playwright/CDP is the in-sandbox option).

  </details>

- **Multiple disks per VM — slice 6 (`move_disk` splitting) — DEFERRED (YAGNI).** Only needed to split a
  *template's own* multiple disks across storages; async (UPID), slow. v1 never needs it (primary comes
  from the clone; secondaries are allocated fresh on their target storage).

- **In-browser visual verification — HARNESS NOW WORKING (this session).** Playwright (host sandbox,
  v1.61.1 + chromium via `npx playwright install chromium --with-deps`) drives the running ddev app at
  `https://convoy.ddev.site`. A reusable login+screenshot script lives in the session scratchpad
  (`shoot.mjs`; `npm i playwright@1.61.1` in that dir first). Login as `visual-admin@example.test` /
  `password`. **Verified this session:** admin dashboard (blue primary + olive neutrals render correctly),
  nodes/servers/locations/users/tokens, node network page (VLAN cards render: vmbr0 "Not VLAN-aware",
  vmbr1 "VLAN 100"), server-create. ⚠️ **See the two-Postgres gotcha below — you MUST seed into the DB
  FPM actually uses (`192.168.107.3`), not the ddev `db` container, or the app won't see your data.**

- **Admin dashboard redesign + olive/blue theme — PALETTE DONE, DASHBOARD BUILT, metrics-deltas (VictoriaMetrics) in progress.**
  - *Palette (DONE, global):* migrated the whole app's theme tokens in `resources/scripts/app.css` to the
    shadcn preset **`b1YoNB40O`** (olive-tinted neutral ramp + **BLUE `--primary`**, not orange), in
    **OKLCH**. Repo was HSL-wrapped, so also unwrapped `hsl(var(--x))` → `var(--x)` in `tailwind.config.cjs`
    **and all ~25 JS/TS call-sites** (Recharts colors in `features/servers/components/client/Graphs/*`,
    `ShowStorageModal.tsx`, `utils/data-table.ts`); the one alpha site → `color-mix(in oklab, … , transparent)`.
    Added `--sidebar-*` + `chart` tokens to the config. **Verified:** `npx tsc` clean + `npx vite build` clean
    (compiled CSS carries oklch; `bg-primary/*` opacity → `color-mix`). **NOT clicked in-browser** — primary
    is now blue *everywhere* (buttons, progress bars, links), so eyeball the running app. Reference preset
    output saved in the session scratchpad `preset-vite/` & `preset-next/`.
  - *Dashboard (BUILT + committed):* hybrid of KPI stat tiles + a *Servers by status* list + a *Needs
    attention* lane (capped at 4 rows, overflow opens a right **`Sheet`**; onboarding checklist when
    `summary.servers === 0`), responsive **node table → stacked cards** below `@2xl`. Lives in
    `features/overview/components/admin/` (`OverviewContainer.tsx` + `NeedsAttentionCard.tsx` + `NodesCard.tsx`
    + `overview-helpers.ts`); added an `indicatorClassName` prop to `LinearProgressBar` for capacity tone
    (neutral <80%, amber ≥80%, `bg-destructive` ≥95%). Container-query layout (`@container`+`@lg:`…), NOT
    viewport breakpoints. **Deviation:** near-capacity meter uses amber (no brand-orange token). tsc + vite
    build clean; **not yet clicked in a browser** (primary is blue now).
  - *Metrics history → VictoriaMetrics (DECIDED, building):* KPI **deltas + sparklines** are backed by
    **VictoriaMetrics**, NOT a Postgres snapshot table (evaluated + rejected Timescale [TSL forbids managed
    DBaaS → not on RDS/CloudSQL, breaks Postgres-only], and InfluxDB [OSS v3 Core caps history to ~72h,
    query-language whiplash]). VM is Apache-2, single binary, Prometheus-compatible, no history/feature gate.
    **VM is OPTIONAL** — when `metrics.victoriametrics.url` is unset the recorder/trends no-op and the
    dashboard still works (deltas just don't show), so it's never a required dependency for operators.
    Local dev: `.ddev/docker-compose.victoriametrics.yaml` (modeled on the repo's `docker-compose.redis.yaml`),
    reachable from `web` at `http://victoriametrics:8428`. Push model: `metrics:snapshot` command (scheduled
    hourly) writes overview scalars via `/api/v1/import/prometheus`; `OverviewService` reads back a
    `query_range` to compute per-KPI delta (vs ~7d ago) + series. DTO: `MetricTrendData` + trends on
    `OverviewData`. Retention is a VM flag (`-retentionPeriod`), not a paywall.
    - **STATUS: BUILT, verified working, visually tested.** Files added/changed: `config/metrics.php`;
       `app/Services/Metrics/VictoriaMetrics.php`
       (client — `writeNow()` via prometheus import, `queryRange()`); `app/Data/Admin/Overview/MetricTrendData.php`
       + `OverviewTrendsData.php`; `app/Console/Commands/Maintenance/SnapshotOverviewMetricsCommand.php`
       (`metrics:snapshot`); `OverviewService.php` (ctor injects VM; added `snapshotMetrics()`, `trends()`,
       `trend()`; `build()` now returns `trends`); `OverviewData.php` (+`trends`); `routes/console.php`
       (hourly schedule, guarded by config); `.ddev/docker-compose.victoriametrics.yaml`; `.env` +
       `.env.example` (`VICTORIAMETRICS_URL`); frontend `Sparkline.tsx` (shadcn `ChartContainer` + Recharts
       `AreaChart`, per maintainer preference) + `MetricTile` in `OverviewContainer.tsx` (delta ▲/▼ + sparkline,
       wired to `data.trends.{servers,nodes,users,backups}`). TS types regenerated (`trends`/`MetricTrendData`/
       `OverviewTrendsData` present). **Verified:** VM up in ddev; write→query loop works from `web`; `ddev exec
       php artisan metrics:snapshot` wrote all `convoy_overview_*` series; `ddev npm run build` clean; `ddev npm
       run tc` clean; PHPStan clean; full Pest clean (**194 passed**). **Visual verification done:** migrated the
       dev DB, seeded disposable dashboard data (`visual-admin@example.test` / `password`), backfilled 15 daily VM
       samples, installed Playwright Chromium/deps ephemerally in the ddev web container, removed `public/hot` so
       Laravel used built assets, and captured desktop/mobile screenshots at `storage/app/visual-dashboard-desktop.png`
       and `storage/app/visual-dashboard-mobile.png`. Bugs found/fixed during visual pass: cached `OverviewData`
       could serialize `nodes` as `{ data: [...] }` after cache hydration (re-apply `withoutWrapping()` after cache
       read; regression assertion added), and mobile dashboard cards clipped horizontally (layout/grid `min-w-0` +
       explicit `grid-cols-1`). Also `ddev restart` logs a benign `scheduler: ERROR (spawn error)` (horizon starts
       fine; pre-existing). VM's instant queries lag ~30s (`latencyOffset`); use `query_range` or explicit `time`.
  - *Caption-color standardization (DONE):* introduced semantic `--label` / `text-label` (aliased to the
    existing muted caption color), added shared `Stat` + `StatLabel` in `components/ui/Typography`, and
    refactored admin dashboard labels plus client `StatisticCard` titles onto the shared style. Verified
    `ddev npm run tc` + `ddev npm run build` clean.
  - *FE overhaul — RE-SCOPED (interview 2026-07-09). READ THIS before touching UI.* The maintainer's
    frustration: the palette token swap changed almost nothing *visible*, and a pure Radix→Base UI primitive
    swap is **invisible by design** — so "do the Base UI migration" was the wrong first target. Re-aimed at
    the **visual result**. Confirmed decisions from the interview:
    - **Target look = shadcn `blocks` / `dashboard-01`.** Layered cards with subtle borders + soft shadow,
      tight KPI tiles (delta + sparkline), data-dense muted tables, generous section spacing, clear
      hierarchy, `rounded-lg`, muted captions, badges. Browse **ui.shadcn.com/blocks** & **/examples**;
      MIT registry blocks, add via `npx shadcn@latest add <block>`. **Lift their composition/spacing/polish,
      don't reinvent.** (NOT Vercel/geist, NOT Linear — those were the other interview options.)
    - **Biggest gaps to fix:** cards are too flat/plain; spacing & density feel generic. These are the
      levers — depth + deliberate spacing.
    - **Base UI is a HARD requirement** (`@base-ui-components/react`), confirmed. Primitives must move off
      Radix. BUT do it *opportunistically per screen* — migrate the primitives a flagship screen actually
      touches; do NOT block visible work on a big-bang 40-component migration.
    - **Sequence = flagship-screen-first.** Perfect ONE screen end-to-end → screenshot → maintainer
      approves the "look" → extract patterns into shared components → roll out. Do not restyle everything
      at once.
  - *Sidebar redesign — FLAGSHIP BUILT + BROWSER-VERIFIED (2026-07-09, commit `157f73bb`). Roll-out to
    remaining screens is what's left.* Done: replaced the hover icon-rail with a **full labeled sidebar**
    (`w-64`, grouped sections under muted caps headers), **contextual drill-down** (client dashboard → a
    server swaps the whole nav), **back button** ("‹ Servers"), **entity context header** (server name +
    icon), and the **subtle Vercel enter transition** (`animate-nav-in` in `app.css`: fade + ~6px translate,
    keyed on `nav.key`, `prefers-reduced-motion` respected). New nav model in `Navigation.types.ts`
    (`SidebarNav`: `key`/`back`/`context`/`groups`) + `normalizeNav` so callers may still pass a flat
    `Route[]`; `AppLayout`/`Header`/mobile `SidebarToggle` all consume it; shared `SidebarContent.tsx` powers
    desktop + mobile sheet. `BrandLink` now a clean "Convoy" wordmark + accessible "Admin" chip (orange
    dropped). Verified in-browser: admin dashboard, client "My Servers", server drill-down (screens all
    render; drill shows back + `Storage & Network`/`Configuration` groups). **Refinements (commits
    `3076bdf2` → `f777f02a`), all browser-verified:** active item is color-only (no font-weight/reflow); no
    hover color transition; bottom Settings replaced by a **collapse toggle** → icon rail that **hover-expands
    as an overlay** (icons sit in a fixed-width centered leading slot so nothing shifts on collapse; hover
    state kept in `use-sidebar-store` so it survives the layout remount on navigation — CSS `:hover` did not,
    because it doesn't re-fire on a stationary cursor after an SPA nav); **direction-aware drill transition**
    (300ms/16px: from the right going deeper, from the left going back — the direction is *frozen per nav.key*
    via refs + a non-reactive store read, else a re-render overwrote the class; and it's **suppressed on the
    first nav render** of a session so a direct URL load doesn't animate); group headers align to the nav-link
    **icon** column; server context icon aligned to that column; collapsed group headers render a **fixed-width
    divider line placeholder** centered in the gap (via transform, layout-neutral) so hovering to expand causes
    **zero vertical layout shift** (measured: item top identical collapsed vs expanded). `ServerSeeder` fixed +
    now takes `SEED_SERVER_USER` (email/id) and `SEED_SERVER_COUNT` env overrides (commit `207a76a7`).
    **Continuation this session:** admin dashboard nav now uses grouped `SidebarNav` sections
    (`Infrastructure` / `Provisioning` / `Administration`); admin server + node layouts now use drilled-in
    `SidebarNav` with back links and entity context headers; the top-right avatar menu now shows a root-admin
    `Workspace` switch between Client Area and Admin Console with the current workspace marked; the drill
    transition now has the **exit** half (`animate-nav-out*`) so old links fade/translate out while new links
    enter; header has a compact **global** command palette (`Ctrl+K`/`/`) backed by the existing `CommandDialog`
    — it lists current dynamic nav first, then client/admin global entries (admin entries only for root admins),
    contextual back shortcuts, common actions (create server/node, workspace/security), and lazy entity search for
    client servers plus admin servers/nodes while the dialog is open.
    Fixed a real browser-caught a11y bug while testing: shared `CommandDialog` now includes an sr-only
    `DialogTitle`. The expanded smoke also exposed real client Security-page console noise: closed authenticator
    dialogs eagerly fetched recovery codes / QR / secret-key endpoints and got 403s; fixed by making those
    sensitive queries lazy and only enabling QR/secret after `enableAuthenticator()` succeeds. **Verified:**
    installed Playwright 1.61.1 + Chromium/deps in the sandbox, browser-smoked login → admin grouped nav → global
    command palette (admin↔client entries) → avatar workspace switch → admin server drill-down → admin node
    drill-down; screenshots in `/tmp/opencode/panel-visual/*.png`; `ddev npm run tc` + `ddev npm run build`
    green (build needed one retry after the known sandbox segfault). **Follow-up after richer command palette:**
    `ddev npm run tc` + `ddev npm run build` green; targeted Playwright smoke passed for lazy entity-search API
    calls + action entries, screenshot `/tmp/opencode/panel-visual/command-palette-entity-search.png`. **Still
    TODO:** optional top workspace/account switcher (design choice). **Note:**
    the admin node nav grouping preserved the existing links to `/servers`, `/ipam`, and `/settings` under a
    node, but those route files still do not exist — pre-existing product/page follow-up, not introduced by the
    grouping. **Also unbuilt (verified 2026-07-09):** the node **Overview** landing itself
    (`routes/_app/admin/nodes.$nodeId/index{,.lazy}.tsx`) is still the scaffold `Hello "…"` placeholder — so
    the node's default page needs building too. All four (`overview`/`servers`/`ipam`/`settings`) are content
    /design calls (what KPIs, which list, which settings), deferred pending maintainer direction. The built
    node sub-pages for reference are `network` and `storages`. Reference spec (Vercel style, maintainer prefers it **over** Cloudflare; 4 screenshots pasted
    2026-07-09):
    - **Full labeled sidebar** (icon + text, always expanded — not a hover rail), with **grouped sections**
      under muted caps **section headers** (cf. Cloudflare's *Observe / Build / Protect & Connect*; Vercel's
      first group is header-less then a divider).
    - **Contextual drill-down:** clicking an **entity** (a **server** — Convoy's analog to Cloudflare's
      **domain**) **swaps the entire nav** to that entity's deeper links. Primary target flow: **client
      dashboard → server**. Items that drill deeper show a right chevron `>`.
    - **Back button at the top** of the drilled-in nav — Vercel style ("‹ <SectionName>"), e.g. "‹ Back to
      Servers" or the server name. (Cloudflare uses "‹ Back to Domains".)
    - **Transition = Vercel's SUBTLE version** (maintainer's explicit pick): old links fade + translate a
      *tiny* amount left, new links fade + translate in a *tiny* amount from the right. **NOT** Cloudflare's
      full-width slide. Keep translate small (~8–12px feel), fast, tasteful.
    - Other Vercel/Cloudflare chrome to consider: top **workspace/account switcher** (Vercel "Eric W's
      projects · Hobby ▾"), a **search / ⌘K** field near the top, pinned **Settings / Manage account** and a
      **user avatar + ⋯ + bell** at the bottom, a collapse toggle. Nav model needs a hierarchy type (groups +
      children + entity-context), replacing the flat `Route[]`. TanStack Router; `AppLayout` passes `routes`
      to `Sidebar`+`Header`; both client (`routes/_app/_dashboard.tsx`) and admin
      (`routes/_app/admin/_dashboard.tsx`) build their own route arrays.
  - *Admin↔client distinction — BUILT + BROWSER-VERIFIED.* The old color-only orange/admin signal was dropped
    from the logo. Root admins now get a **Workspace** section inside the top-right avatar menu
    (`components/ui/Navigation/Avatar.tsx`) with Client Area + Admin Console links and a `Current` marker, so the
    context is text-visible and navigable. Verified by Playwright smoke + `ddev npm run tc` + `ddev npm run build`.

---

## Gotchas / must-know (still live)

- **`db` name-collision (PHP-FPM vs `ddev exec` hit different databases) — ROOT-CAUSED + FIXED 2026-07-09
  (commit `f14ddd0d`). Left here because it cost ~1h and the mechanism recurs in any sandbox+ddev setup.**
  Symptom: `getent hosts db` / `ddev exec psql .../db` resolved `db` → **172.20.0.2** (the `ddev-convoy-db`
  container), but **PHP-FPM's libpq resolved `db` → 192.168.107.x** (a host-network Postgres reached via the
  docker gateway) — a *different* DB with different data, same `host=db` DSN. Confirmed with
  `inet_server_addr()` from a debug route (FPM) vs psql (CLI). **Cause:** the web container's
  `/etc/resolv.conf` has `search claude-panel.docker.internal` + `ndots:0` and forwards misses to the sandbox
  **host resolver**, which runs a **wildcard DNS** answering `db.claude-panel.docker.internal` with a host
  Postgres. `getent` got the bare-name container record; libpq fell through to the search-qualified wildcard.
  (Also why the FPM DB's IP *drifted* `.3`→`.2` between calls.) **Fix:** a `post-start` hook pins `db` in the
  web container's `/etc/hosts` to the real container IP (`getent hosts db`), so nsswitch `files` answers before
  DNS and nothing falls through — FPM and CLI now share one DB. Re-resolved each start to survive IP drift.
  **So plain `ddev exec php artisan migrate` and tinker now target the same DB the app reads** — no more
  `DB_HOST=` overrides needed. (During the bug, the VLAN migration had to be run twice; both DBs got it.) If
  this ever resurfaces, verify `ddev exec grep -w db /etc/hosts` shows `172.20.0.x db`.
  **2026-07-09 follow-up observation:** during the Playwright sidebar smoke, `ddev exec psql` against
  `172.20.0.2/db` returned seeded names (`servers.id=1` → `web-1`, `nodes.id=1` → `Sydney Compute 01`), while
  the browser-rendered app showed different app-visible rows (`doloribus` / `et aut`) even after `ddev restart`
  and with `/etc/hosts` correctly pinned. Cause not chased because it did not affect the UI smoke; avoid
  hardcoding `psql` names in browser assertions until FPM/app-visible DB is re-confirmed with a debug route or
  equivalent.
  **Portable across sandboxes (incl. a different Docker-sandbox / codex-opencode setup):** the fix is in the
  committed `.ddev/config.yaml` and its logic hardcodes nothing sandbox-specific — `getent hosts db` gets the
  container IP from docker's embedded DNS (authoritative for the service name) regardless of the host's search
  domain, and it's a no-op where there's no collision. So it travels with the repo and re-applies on
  `ddev start` in any environment; nothing to redo next session.

- **Intermittent `exit 139` (SIGSEGV) running heavy commands in the sandbox — ENVIRONMENT, NOT a repo bug; no
  core fix, just retry.** Seen sporadically on `ddev npm run build`, `php artisan tinker`, `cache:clear`, and
  PHPStan's *parallel* workers — i.e. across node AND php, so it's not any one tool. It's an artifact of this
  Docker sandbox (most likely CPU emulation and/or memory pressure — the same class of thing that makes the
  amd64-only pgloader segfault under arm64 qemu), so there is **nothing to fix in the repo** and no config
  change is warranted (forcing e.g. PHPStan serial in `phpstan.neon` would just penalise real CI for a sandbox
  quirk). **Mitigation:** re-run the command (it usually succeeds within a couple of tries); run PHPStan with
  `--debug` (serial) to dodge the parallel-worker crash. A different sandbox (e.g. next session's) may not hit
  it at all. Do **not** conflate this with the `db` name-collision above — that one *is* fixed at the core.
- **Run tests with `ddev exec vendor/bin/pest` (or `ddev exec php artisan test`), NOT `ddev artisan
  test`.** The ddev global-command wrapper segfaults (exit 139) booting the test runner in this sandbox —
  `ddev artisan tinker`/`migrate` are fine, so it's a wrapper quirk, not a regression. Last green:
  **171 passed** on `next`.
- **Test DB isolation.** Suite uses `RefreshDatabase` (`migrate:fresh` resets Postgres sequences) against
  a dedicated **`db_test`** DB (ddev `post-start` `createdb`, idempotent). `tests/bootstrap.php` redirects
  onto `db_test` when `DB_TEST_DATABASE` is set — done in bootstrap (not phpunit `<env>`) because ddev
  exports `DB_DATABASE` into `$_SERVER`, which Laravel's `Env` reads before `$_ENV`/`getenv`. CI is
  untouched (doesn't set the var). **CI already runs on Postgres 17** — `.github/workflows/tests.yml`
  spins up a `postgres:17` service and `.env.ci` is `DB_CONNECTION=pgsql`; the earlier "throwaway MySQL /
  add a Postgres CI service" note is obsolete, nothing to do there. ⚠️ Never point `RefreshDatabase` at
  dev `db` — it will wipe it; recover via
  `ddev snapshot restore postgres-baseline`.
- **PHPStan is at ZERO** (level 5, `app/`). `ddev exec ./vendor/bin/phpstan analyse --memory-limit=4G` is
  green — keep it green. (Getting there fixed real runtime bugs: `ServerDeletionService` enum-vs-string
  comparison broke every admin deletion; passkey actions TypErrored under webauthn-lib ^5 — see git
  history if these resurface. **`PublicKeyCredentialSource` → `CredentialRecord` migration DONE**
  (commit `47d9607c`): `Passkey::$data` now persists/rehydrates the base `CredentialRecord`, so
  `AuthenticatorAssertionResponseValidator::check()` no longer takes its deprecated-argument path. The two
  classes' serialized JSON is byte-identical (verified via the shared normalizer + a tinker round-trip), so
  **no data migration** was needed for existing rows. Pre-6.0 webauthn item cleared. **SUPERSEDED
  2026-07-09** (commit `20d8a442`): `spatie/laravel-passkeys` was adopted and the model now stores the
  package-default `PublicKeyCredentialSource` again — but the package's `CredentialRecordConverter` downcasts
  to a base `CredentialRecord` before calling the validators, so the deprecated-argument path stays avoided.
  See the "Adopt `spatie/laravel-passkeys` — DONE" entry under *Next up*.)
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
