# v10 (`next`) rewrite — working handoff

Living notes for shipping `next` (v10) as the new trunk. This file tracks *what's done*
(one-line pointers — git history holds the detail) and *what to pick up next*, so a cold start
doesn't re-derive it. Remaining visual-system work is tracked in
[frontend-overhaul-audit.md](frontend-overhaul-audit.md).

Last updated: 2026-07-14 (session: **shared menu/popover and accessibility slice completed** — migrated
shared DropdownMenu and Popover from Radix to `@base-ui/react`, ported nova popup/item styling, added and
applied destructive menu-item treatment, normalized Command/resource-combobox controls, aligned and
browser-verified the real login OTP flow, fixed form-control semantics and icon-only accessible names, and
verified keyboard/focus/dialog handoff plus 390px behavior. `tc`, production build, and the full regression
suite are green; see the dedicated entry under open follow-ups. — prior: **frontend handoff branches
reconciled** — retained the richer
admin node Overview and its live PVE status polling while integrating `next`'s node-scoped Servers
page, editable Settings page, nova `Field`/`InputGroup` and form refinements, mobile admin `Item`
rows, backup-row rollout, generic OIDC provider, and bandwidth backend. The node Overview replaced the
scaffold route with a responsive, data-dense three-card Overview for live CPU/memory/load/uptime,
configured memory allocation, hardware/topology, root filesystem, PVE/kernel/boot, and endpoint
details. Added a saved-node `/status` endpoint over `ProxmoxStatusRepository`, expanded the status
DTO to model the documented PVE status response (including a backed boot-mode enum), and poll it
every 30s with an offline/retry state. `DevNodeSeeder` resolved the configured PVE 9.2.2 node; the
repository and authenticated browser route both returned real live values. Playwright desktop +
390px mobile passed with no console errors or horizontal overflow; screenshots:
`storage/app/node-overview-{desktop,mobile}.png`. Focused Pest 10 + full Pest 226, PHPStan-zero,
`tc` and `build` are green after reconciliation; full Pest 252 and PHPStan-zero also pass. — prior:
**shadcn Item-pattern rollout completed** - converted the final
hand-rolled list, `InstallingServer` / `DeploymentStepRow`, to an always-visible compact `ItemGroup`
of muted `Item` rows; retained polling, timing, byte/percentage progress, errors, and retry behavior,
and restored standard Card spacing. Browser-verified through the real authenticated server route and
deployment API at desktop + 390px mobile with deterministic running and failed deployment records;
list/listitem semantics, progress, long failure detail, and retry footer all passed. Screenshots:
`/tmp/opencode/deployment-timeline-{desktop,mobile}.png` and
`/tmp/opencode/deployment-timeline-failed-{desktop,mobile}.png`. `tc` + `build` green (build passed on
the documented retry after sandbox SIGSEGV). Live PVE credentials/node were available and seeded, but
an actual clone was not required for deterministic UI-state coverage. The FPM/CLI DB split warning
below reproduced (`FPM=192.168.107.2`, CLI=`172.20.0.4`) despite the hosts pin; browser fixtures were
therefore seeded with an explicit `DB_HOST=192.168.107.2`. **No hand-rolled entity/status lists remain
in this rollout.** - prior: rolled the pattern to server Security SSH keys [`397d9441`], client Overview
IPAM [`7916614f`], and account passkeys [`a712ac4e`]; documented live-node e2e seeding in AGENTS.md. - prior:
**SSO Deliverable 2 — OIDC Relying Party via Laravel Socialite** [role
locked = RP, not Provider]: config-gated optional providers [google/github/gitlab], `oauth_connections` +
`OAuthConnection`, `OAuthAuthenticationService` link/register policy [verified-email link, non-admin
auto-register off by default], `Auth\OAuthController` redirect/callback [login when logged-out, link when
logged-in], login-page "Continue with" buttons + account "Connected Accounts" card, curated HasErrorCode
exceptions; Pest 224 + PHPStan-zero + tc/build green; NOT live-tested against a real IdP [no creds/callback
in-sandbox]) — prior: **adopted `spatie/laravel-passkeys`** [commit `20d8a442`] — backend-only
swap, package-default `PublicKeyCredentialSource` storage, `user_id` schema kept via a thin model subclass,
curated error-code exceptions + canary/localhost origins preserved via subclasses; deleted the four copied
actions/serializer. **Live WebAuthn register→login→re-auth PROVEN** via a Playwright CDP virtual authenticator
[19/19 steps]; found+fixed a route-binding regression the spatie base class caused [commit `a5e79adc`]; full
Pest 205 + PHPStan-zero green) — prior: passkey PublicKeyCredentialSource→CredentialRecord migration [interim, now superseded]; corrected stale CI-on-MySQL note [CI already Postgres 17]; recorded node Overview/servers/ipam/settings pages as still-unbuilt design follow-ups) — prior: VLAN committed; visual harness up; FE overhaul re-scoped; Vercel sidebar built+polished; admin grouped nav + avatar workspace switch browser-verified; nav exit transition + global command palette; account security lazy sensitive queries; ddev db collision fixed at the core/portable; seeder env overrides)

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

- **Client My Servers collection — DONE (2026-07-14).** Replaced the bespoke per-server cards with the
  shared muted `ItemGroup` row pattern, removed the hardcoded placeholder IP, and packed status plus
  CPU/memory/disk values into a responsive definition list using `byte-size`. The page now has matching initial
  loading rows and a contextual no-servers state; empty collections do not render a stray paginator. Browser
  verification covered a real populated account, delayed loading, an intercepted empty response, the existing
  action menu, and 390px layout with no console errors or horizontal overflow. `tc` and production build pass.

- **Shared menu/popover and accessibility slice — DONE (2026-07-14).** Shared `DropdownMenu` and
  `Popover` now use `@base-ui/react`; the two superseded Radix packages were removed. Compatibility wrappers
  preserve existing `asChild` consumers through Base UI's `render` API. Menus/popovers now use nova's compact
  popup/item chrome, and irreversible menu actions use the shared `variant="destructive"`. Command search and
  `ResourceComboboxForm` use the standard 32px `InputGroup`; the combobox now exposes its form ID,
  `aria-invalid`/description linkage, expanded/controls state, disabled submission state, and an accessible
  search label. OTP slots use the same 32px/ring/radius treatment, the authenticator input is properly labelled,
  and formerly silent icon-only actions now have accessible names. The node-create memory Amount label now
  targets the actual input. Browser verification covered menu keyboard navigation and focus return,
  checkbox-menu semantics/stay-open behavior, menu→responsive confirmation-dialog handoff, Command keyboard
  selection, real node selection with a valid controlled popup ID and focus return, the real 2FA route, and
  desktop/390px overflow with no settled-page console errors. Base UI requires `Menu.GroupLabel` to be nested
  in `Menu.Group`; violating that contract throws production error #31, so grouped labels in the DataTable,
  SSH-key, and avatar menus retain that structure.

- **Design-input queue — RESOLVED with the maintainer 2026-07-15.** All four open questions are now
  decided; build against these, don't re-litigate:
  1. **Inheritance presentation = segmented `Inherit | Custom`.** A two-segment control sits at the top of
     an overridable field; `Inherit` shows the resolved *effective* value as muted text beneath (e.g.
     "Effective: Throttle to 10 MB/s (from global)"), `Custom` swaps in the real inputs. Applies to the
     per-node and per-server overage-penalty / speed-cap overrides.
  2. **Global admin Settings IA = sub-nav drill-down in the sidebar. — BUILT 2026-07-15.** Settings
     is a drilled-in `SidebarNav` like nodes/servers, one route per section
     (`/admin/settings/<section>`), NOT a single sectioned page and NOT tabs. `BandwidthSettings` is
     the first section. See the **Admin Settings screen** entry below.
  3. **Workspace switcher stays in the avatar menu.** The existing root-admin Workspace section (Client
     Area / Admin Console + `Current` marker) is the only path; no top-chrome switcher. **Item closed.**
  4. **Destructive buttons keep nova's soft tint.** `bg-destructive/10 text-destructive` stays; do NOT flip
     `Button.variants.ts` to solid red. **This settles the "DECISION TO REVISIT (2026-07-10)" below.**

- **Admin Settings screen — BUILT (2026-07-15), infra now exists.** The "there's no settings-screen
  infra yet" blocker is gone. `/admin/settings` is a drilled-in layout
  (`routes/_app/admin/settings.tsx`, a sibling of `nodes.$nodeId.tsx` — drilled-in layouts own their
  own `AppLayout`, so it lives **outside** `_dashboard`), with `back: Admin`, a "Settings /
  Panel-wide defaults" context header, and one route per section. `settings/index.tsx` redirects
  `/admin/settings` → the first section. A **Settings** item was added to the admin sidebar's
  Administration group.
  - **Adding a section:** create `routes/_app/admin/settings/<name>.{tsx,lazy.tsx}` (+ a
    `features/settings/api.ts` entry) **and** add the nav item in `settings.tsx`. Only add the nav
    item once the route file exists — a nav pointing at nothing is how the client server tabs ended
    up declared-but-missing. Bandwidth is currently the only section; General/Auth/Metrics from the
    IA sketch are **not** built.
  - **Route tree:** `tc` fails against new route files until `npm run build` regenerates
    `routeTree.gen.ts` — build first, then typecheck.
  - **Verified in-browser:** admin sidebar → Settings → redirects to `/admin/settings/bandwidth`;
    drilled nav shows back/context/section; form loads the real global default (Throttle, 1 MB/s);
    save → API `{throttle, 25000000}`; reload rehydrates. No Inherit segment on the global tier.

- **Segmented control primitive — BUILT (2026-07-15).** Decision 1 above needs a segmented control, so
  `Toggle` + `ToggleGroup` now exist as shared primitives (`components/ui/Toggle`, `components/ui/ToggleGroup`),
  ported from nova's source (`apps/v4/styles/base-nova/ui/{toggle,toggle-group}.tsx`) — which is already
  **Base UI**-backed, so this satisfies the hard Base-UI requirement rather than fighting it. `spacing={0}`
  gives the connected segmented look; `multiple={false}` gives single-select. The hand-rolled MiB/GiB toggle in
  `features/nodes/components/Create/SpecificationsSettingsForm.tsx` was refactored onto it (first consumer).
  Use these for the `Inherit | Custom` control — at nova's default `h-8` they need no size overrides.
  - ⚠️ **nova-vs-Base-UI gotcha (cost real time; expect it in future nova ports).** nova's toggle-group source
    styles orientation with `group-data-horizontal/…` / `data-vertical:`, i.e. **bare** `[data-horizontal]`
    attribute selectors. `@base-ui/react` 1.6.0 emits **`data-orientation="horizontal"`** instead — its default
    state→attribute mapping (`internals/getStateAttributesProps.js`) only emits a bare `data-<key>` when the
    state value is boolean `true`, and stringifies otherwise. The string `data-horizontal` appears **nowhere**
    in Base UI's dist. So nova's classes compile fine and then **silently never match** (segmented corners stay
    square). Ported classes are keyed off `data-[orientation=…]` instead. **Lesson: Tailwind never errors on a
    class that matches nothing — after porting nova source, verify against the rendered DOM, not just a green
    build.**
  - **Verified in-browser** (Playwright, real authenticated `/admin/nodes/create`): group renders
    `data-orientation="horizontal"` + `data-spacing="0"`, computed `gap: 0px`, asymmetric rounding
    (MiB `10px/0`, GiB `0/10px`) = connected look, `aria-pressed` flips with `bg-muted` on the pressed item,
    clicking the pressed item does **not** deselect (guard in `onValueChange` keeps one unit always selected),
    and MiB↔GiB conversion is intact (2048 MiB↔2 GiB, 4 GiB→4096 MiB, 1.5 GiB→1536 MiB). `tc` + `build` green.
  - **Visual delta (accepted by the maintainer):** the old MiB/GiB toggle was an iOS-style segmented control
    (muted *track*, selected item raised on `bg-background` + shadow). nova inverts the polarity — no track,
    transparent by default, **selected** item gets `bg-muted`. Inside the `h-8` `InputGroup` addon the item
    height is overridden to `h-5` (nova's smallest `sm` is `h-7`, still too tall).
  - **Known-deferred, unchanged:** the a11y nit from the node-create redesign still stands — `FormControl`
    wraps `<InputGroup>`, so the "Amount" label isn't associated with the inner `<input>`.
  - **`components.json` is stale** (`"style": "new-york"`). Do **not** `npx shadcn@latest add <component>` —
    it would pull new-york + Radix, wrong on both axes. Port nova's source by hand, as done here.

- **Bandwidth rate-limiting rework (GitHub #108) — COMPLETE.**
  Backend (P0–P4) is shipped and tested: a persistent **per-server speed cap**
  (`servers.speed_limit`, bytes/s) plus a **configurable overage penalty**
  (throttle-to-rate or disconnect the NIC) resolved by a **server → node → global**
  cascade (`servers.overage_penalty` / `nodes.overage_penalty` json overrides →
  `App\Settings\BandwidthSettings` global default via `spatie/laravel-settings`).
  Quota reset moved to a **per-server day-of-month anchor** (`bandwidth_reset_day`,
  daily sweep). Enforcement was **re-architected into a per-server
  `SyncServerRateLimitJob` batched per node** (was one node-wide loop). Also fixed:
  `RateLimitCast` now uses Proxmox's decimal MB (was binary MiB, ~4.86% off) and the
  `-1`=unlimited quota no longer false-throttles. Scheduler (`routes/console.php`) is
  now live for `sync-usages` / `reset-usages` / `sync-rate-limits`.

  **Frontend — all four items DONE (2026-07-15):**
  1. **Speed cap on server creation — DONE.** `Speed Limit (MB/s)` in the create wizard's
     `LimitsForm`, mapped to `limits.speed_limit` in bytes/s. The backend already accepted it
     (`StoreServerRequest` + `ServerCreationService`), so this was frontend-only.
     **Blank must omit the key, not send `0`:** the column is *null = unlimited*, and a `0` would
     cap every NIC at zero — hence the `speedLimitSchema` preprocess (mirroring the existing
     `vlanTagSchema`) and the `speedLimit: ''` default. Guarded by a new
     "leaves the speed limit null when none is given" test; the set-a-value direction was already
     covered by "persists the speed cap and anchors the bandwidth reset day".
     ⚠️ **Not fully proven:** the field renders blank with its helper text on the real wizard
     (browser-checked), and the null/uncapped contract is tested server-side, but the **MB/s →
     bytes/s conversion has not been driven through an actual create POST** — that needs a full
     wizard run against a real node (use the deferred-OS + `should_create_vm: false` path, as
     `ServerCreationDiskTest` does, to avoid Proxmox). It's plain arithmetic + JSON, but this repo
     has **no JS test runner** (no `test` script in package.json), so nothing else covers it.
  2. **Per-server speed cap + overage-penalty override — DONE.** The admin server
     drill-down now has a **Build & limits** section at `/admin/servers/{id}/settings`,
     backed by the previously unwired `updateBuild` endpoint. The page uses the standard
     dense card/grid pattern for compute, backup, and bandwidth limits; the bandwidth card
     adds decimal `Speed Limit (MB/s)` plus the shared `OveragePenaltyFields` with the
     resolved node-tier penalty shown while inheriting.
     - `ServerData` now exposes `speedLimit` and the server's own `overagePenalty`; its
       already-loaded `NodeData` supplies node override → global fallback context.
     - Blank speed cap deliberately sends `null` (clear to uncapped), while custom values
       convert from decimal MB/s to bytes/s. MiB resource/quota fields preserve the `-1`
       unlimited sentinel instead of multiplying it.
     - `updateBuild` now accepts the real `backup_count_limit` / `backup_size_limit` fields.
       `address_ids` is optional and `syncAddresses()` only runs when it is explicitly
       present, so a limits-only save cannot silently detach every IP address.
     - **Verified:** 2 focused endpoint tests (persist + clear), full Pest **265** / 707
       assertions, PHPStan zero, `tc`, and production build. Authenticated Playwright on
       the real app proved Custom `12.5 MB/s` + throttle `7 MB/s` → API `12,500,000` +
       `7,000,000` bytes/s → reload rehydrates → Inherit/blank clears both to `null`.
       Desktop and 390px mobile had no console errors or horizontal overflow. The seeded
       server was a DB-only factory record; no VM was cloned or provisioned on PVE.
  3. **Per-node overage-penalty override — DONE.** Shipped on the node settings page as a
     "Bandwidth" card using the decided segmented `Inherit | Custom` control.
     - **Shared, reusable pieces** (built for item 2 and 4 to consume, not node-specific):
       `features/bandwidth/overage-penalty.ts` (zod fields + `refineOveragePenalty`
       conditional validation, `overagePenaltyDefaults`, `overagePenaltyPayload`,
       `describePenalty`, `BYTES_PER_MB`) and
       `features/bandwidth/components/OveragePenaltyFields.tsx` (the control; props
       `inheritedFrom` + `inheritedLabel`).
     - **Backend:** `NodeData` now exposes `overagePenalty` (the node's own override, null =
       inherit) **and** `defaultOveragePenalty` (the global tier, read-only, resolved via
       `OveragePenaltyResolver::global()`) so the UI can print the effective value. `updateNode`
       **always sends** `overage_penalty` — `null` is meaningful (it clears the override), so
       unlike the token fields it must never be omitted.
     - **Units:** UI is decimal **MB/s**, stored bytes/s (`BYTES_PER_MB = 1_000_000`, matching
       `RateLimitCast`'s decimal MB). Global default 1 MB/s renders as "Throttle to 1 MB/s".
     - **Verified:** 4 new Pest tests (persist / clear-to-inherit / exposes both fields /
       null when inheriting) — full Pest **256**, PHPStan zero, `tc` + `build` green. Browser
       round-trip on the real settings page: Inherit shows "Effective: Throttle to 1 MB/s (from
       global)"; Custom → save → API `{throttle, 10000000}`; reload rehydrates Custom/10;
       Disconnect hides the rate input → `{disconnect, null}`; back to Inherit → `null`.
  4. **Global default — DONE.** `BandwidthSettings` is now UI-editable at
     `/admin/settings/bandwidth`, the first section of the new admin Settings screen (see the
     **Admin Settings screen** entry below). Renders `PenaltyActionFields` **without** the
     Inherit segment, since the global tier has nothing above it. Backend:
     `Admin\Settings\BandwidthSettingsController` (show/update) + `BandwidthSettingsData` +
     `UpdateBandwidthSettingsRequest`, routed at `/settings/bandwidth` in `routes/api-admin.php`.
     Reads/writes go through `OveragePenaltyResolver::global()` so the screen and the enforcement
     path agree on how the stored strings become a typed penalty. **Switching to `disconnect`
     deliberately preserves the stored rate** (it isn't part of that penalty, but discarding it
     would lose the operator's figure the moment they flipped back). 6 new Pest tests.
  UX note: **disconnect** is a hard penalty (guest keeps the NIC but loses carrier) —
  label it clearly; it's reversible. Show the resolved *effective* value where an
  override is left on "inherit".

  ⚠️ **Base UI `Select` controlled-value gotcha (found + fixed here; will bite again).**
  `components/ui/Forms/SelectForm.tsx` now passes `value={field.value ?? null}`, and the
  `?? null` is **load-bearing**. Base UI reads `value={undefined}` as *uncontrolled* and then
  **ignores every later value**, so the trigger stays stuck on its placeholder
  (`data-placeholder` set) even though RHF holds a real value. This bites any form that mounts
  before its `form.reset(...)` lands — i.e. every page that resets from a query, including the
  node settings page. Text inputs hide the problem (`value ?? ''`), so a select is where it
  surfaces. `SelectForm` was previously **unexported and unused**, which is why this was latent;
  it's now exported from `Forms/index.ts`.

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

- **Replace the SSO-token hack — DELIVERABLE 1 (signed URL) DONE (commit `0fa92b40`); DELIVERABLE 2
  (OIDC Relying-Party via Socialite) DONE (2026-07-10).** Two distinct deliverables, don't conflate — and
  note **both coexist**: (1) is an admin/integration-initiated deep link (WHMCS already knows the user);
  (2) is user-driven federated login. (2) does *not* make (1) dead code.
  - **(1) first-class signed-URL — DONE.** Swapped the bespoke app-key-JWT (`getSSOToken` + `/consume-token`
    + `Auth\LoginController`, all removed) for a Laravel signed URL: `Admin\UserController::getSSOToken` now
    mints `URL::temporarySignedRoute('auth.sso.consume', now+config('sso.link_ttl'), ['uuid','nonce'])`;
    `Auth\SsoController::consume` (route `/api/auth/sso/{uuid}`, `signed` middleware) verifies the HMAC+expiry,
    burns the nonce via `Cache::add` (single-use → replay 401), resolves the user by uuid, logs in, and writes
    an audit entry to `config('sso.audit_channel')`. Scoped by the minting **application token's existing
    abilities** (`users:write`) — that *is* the "per-integration key" (each integration has its own revocable
    Sanctum token), so no new model. `SSOTokenData` now returns `link` (absolute signed URL), not `token`;
    the external plugin just redirects the browser to it. `config/sso.php` (`link_ttl`, `audit_channel`) +
    `.env.example` knobs (both have defaults). `JWTService` **kept** (still used by Coterm). Pest **209**,
    PHPStan zero, tc+build green. Signature mint/validate proven correct in-process (`hasValidSignature=true`,
    tamper=false) + via feature tests through the real `ValidateSignature` middleware (consume login/redirect,
    tamper 403, expiry 403, replay 401, unknown-user 401, admin mint round-trip). *Live-curl caveat:* an
    in-container `curl` of a minted link 403s because ddev's router terminates TLS and FPM sees `http` while
    the URL was signed `https` (no `TrustProxies`) — same proxy/origin artifact as the passkey ceremony, not a
    code bug; the browser path (correct forwarded headers) is unaffected.
  - **(2) OIDC/OAuth SSO — DONE as Relying Party via Laravel Socialite** (role locked with maintainer
    2026-07-10: RP, "log into Convoy with an external IdP"; **Provider/Passport direction NOT built** — revisit
    only if "log in *with* Convoy" is ever wanted). `composer require laravel/socialite` (^5.28; first-party,
    high-rep). Feature is **OPTIONAL** (like VictoriaMetrics): a provider only appears once its
    `oauth.providers.<p>.enabled` flag is true AND `services.<p>.client_id` is set, so a bare install keeps
    plain email/password + passkey login untouched.
    - **Config:** `config/oauth.php` (providers `google`/`github`/`gitlab` with `enabled`+`label`;
      `registration` = auto-provision unknown identities [default off]; `link_by_verified_email` = link to an
      existing user by verified email on first sign-in [default on]). `config/services.php` gained
      client_id/secret/redirect per provider; `.env.example` documents the knobs.
    - **Schema/model:** `oauth_connections` (`user_id` FK, `provider`, `provider_id`, `name`, `email`,
      `last_used_at`, unique(`provider`,`provider_id`) — the per-sign-in lookup key). `App\Models\OAuthConnection`
      (binds by `id`), `User::oauthConnections()` hasMany.
    - **Policy** lives in `App\Services\Auth\OAuthAuthenticationService`: existing connection wins → else
      link-by-verified-email → else auto-register (never `root_admin`; **only from a verified email** even with
      registration on) → else refuse (`OAuthAccountNotProvisionedException`). GitHub/GitLab emails are treated as
      implicitly verified (their Socialite drivers only return verified primaries); everyone else needs an
      explicit `email_verified` raw claim (Google sends it).
    - **Flow:** `Auth\OAuthController` `redirect`/`callback` at `/api/auth/oauth/{provider}/{redirect,callback}`,
      placed **outside** the guest/auth groups on purpose — logged-out = login/provision, logged-in = **link** to
      the current user (callback branches on `Auth::check()`). Browser-redirect endpoints, so failures redirect
      back to the SPA with an `?oauth_error=<code>` (login) / `?oauth_linked=<provider>` (account) param the
      frontend toasts — **not** JSON (the render hook only fires for `expectsJson`). Open-redirect guard on the
      `intended` param (relative same-origin only). Curated `HasErrorCode` exceptions:
      `OAuthProviderNotEnabledException` (404), `OAuthAccountNotProvisionedException` (403),
      `OAuthIdentityAlreadyLinkedException` (409).
    - **Frontend:** enabled providers are injected via `IndexController` → `window.SiteConfiguration.oauthProviders`
      (typed in `globals.d.ts`; helpers in `features/auth/oauth.ts`). Login page renders `OAuthProviderButtons`
      ("Continue with …", full-page redirect) + toasts `oauth_error`. Account **Security → Connected Accounts**
      card (`OAuthConnectionsCard`) lists enabled providers, Connect (redirect) / Disconnect (JSON DELETE
      `/api/client/account/oauth-connections/{id}`, owner-scoped 404); card renders nothing when no provider is
      configured. Client half: `Client\Account\OAuthConnectionController` + `OAuthConnectionData` DTO.
    - **Verification:** Pest **224 passed** (15 new: redirect/disabled-404, existing-connection login, link-by-
      verified-email, unverified-no-link, registration on/off, unverified-no-provision, invalid-state, link,
      cross-user conflict, account list/unlink/foreign-404 — Socialite driver mocked). PHPStan zero; `ddev npm run
      tc` + `ddev npm run build` clean. **Not live-tested against a real IdP** (needs real client id/secret +
      an allowlisted callback URL, neither available in-sandbox) — the redirect handshake itself is unexercised
      end-to-end; everything up to and past `Socialite::driver()->user()` is covered with a mock.

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
  - **Verification — LIVE WebAuthn ceremony PROVEN end-to-end (2026-07-09).** Drove a real register→login
    round-trip in headless Chromium (Playwright 1.61.1) with a **CDP virtual authenticator**
    (`WebAuthn.addVirtualAuthenticator`, ctap2/internal/resident-key/UV), against the running ddev app,
    exercising the actual endpoints in-page (native `PublicKeyCredential.parse{Creation,Request}OptionsFromJSON`
    + `credential.toJSON()`). **All 19 steps green:** password login → confirm identity → get registration
    options → authenticator creates credential → verify-registration (StorePasskeyAction stores it) → list →
    rename → logout → **passkey login** (FindPasskeyToAuthenticateAction) → correct user → **confirmable
    re-auth via passkey** (ConfirmableIdentityController) → delete → empty list. Also full Pest **205 passed**
    (202 + 3 new binding tests), PHPStan level-5 **zero**.
    - **Origin note:** `config/app.php` hardcodes `'version' => 'canary'`, and our
      `ConfigureCeremonyStepManagerFactoryAction` restricts origins to `localhost` on local+canary (faithful
      to the old code, which assumes `APP_URL=http://localhost` in dev). The ddev app runs on
      `https://convoy.ddev.site`, so the live ceremony was proven with `version` temporarily flipped to
      `production` (the mainline path: default `CheckOrigin`, real https host). Reverted after.
    - **Regression found & fixed while testing (commit `a5e79adc`):** because `App\Models\Passkey` now
      extends the **spatie** model, Laravel's **implicit route-model binding stopped resolving `{passkey}`**
      (controller/policy got an *empty* Passkey, id null) → account passkey **rename/delete 403'd** (empty
      model fails `can:update,passkey`), and without the gate 500'd. Sibling routes are unaffected (Server
      uses explicit `Route::bind`; api-keys/ssh-keys/sessions extend `App\Models\Model`). Fix: an explicit
      `Route::bind('passkey', …)` in `AppServiceProvider`, mirroring `server`. Guarded by
      `tests/Feature/Client/Account/PasskeyRouteBindingTest.php`. (This bug was latent — passkey rename/delete
      were never browser-tested before; the swap to the spatie base class is what exposed it.)
    - **Two-Postgres gotcha still live:** FPM talks to a *different* Postgres (`192.168.107.2`) than
      `ddev exec` (`172.20.0.3`), so the disposable test user had to be seeded into FPM's DB via
      `DB_HOST=192.168.107.2 php artisan …`. See the gotchas section. Tinker remained unusable (persistent
      SIGSEGV), so all CLI probing went through artisan commands / the test runner.

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
    - **Base UI is a HARD requirement** (`@base-ui/react`), confirmed. Primitives must move off
      Radix. BUT do it *opportunistically per screen* — migrate the primitives a flagship screen actually
      touches; do NOT block visible work on a big-bang 40-component migration.
    - **Sequence = flagship-screen-first.** Perfect ONE screen end-to-end → screenshot → maintainer
      approves the "look" → extract patterns into shared components → roll out. Do not restyle everything
      at once.
  - *FE overhaul — CORRECTED TARGET + LARGELY EXECUTED (2026-07-10, on `next`). SUPERSEDES the
    "target = blocks/dashboard-01" line above.* The real reference is the polished **example cards on
    shadcn's CREATE page** — source `shadcn-ui/ui` `apps/v4/registry/bases/base/blocks/preview-02/cards/*.tsx`,
    rendered under the create-page default **base variant, style `nova`** (`registry/config.ts` `DEFAULT_CONFIG`:
    base=base, style=nova). **Match nova's concrete VALUES in plain Tailwind — NOT new-york-v4 (it deviates)
    and NOT the `cn-*` classes (that's shadcn.com theming-playground infra, not a product practice).** Exact
    nova values live in `apps/v4/styles/base-nova/ui/*.tsx`. Shipped: `Item`/`ItemGroup`/`ItemMedia` (`variant=muted`
    = the depth) + `OverflowItemGroup` (capped list + right Sheet), `CardAction`, `Empty` primitive (+
    `SimpleEmptyState` rewired to it). Card = flat `ring-1 ring-foreground/10` (not border+shadow), 16px padding,
    `CardTitle text-base font-medium` (500, not semibold), muted `CardFooter`. Controls `Input`/`Label`/`Button`
    → nova (`h-8`, `rounded-lg`, no shadow, `ring-3` focus). v4 shadow scale everywhere. Content column capped
    `max-w-[1600px]`. Rolled out: whole client Security page + server ISO/SSH-keys tabs; every other card inherits
    the chrome globally. Browser-verified across login/security/dialog/server tabs. **DECISION TO REVISIT
    (2026-07-10):** the `destructive` Button uses nova's **soft tint** (`bg-destructive/10 text-destructive`),
    kept as-is for now — but *every* usage is an irreversible-confirm button (delete modals, Kill/Stop VM,
    disable 2FA, delete passkey). If it ever reads underpowered, flip that one variant back to **solid red** in
    `components/ui/Button/Button.variants.ts` (one line; won't clash with nova since destructive is confirm-only).
    **Done:** `Field`/`InputGroup` primitives exist; legacy `Form*` wrappers now inherit nova field slots/text
    rhythm and checkbox wrappers use gap-based layout instead of old `space-*` overrides; admin-dashboard
    `MetricTile` uses the shared `Card` chrome; client backup rows use muted `ItemGroup`/`Item`; admin
    nodes/locations/tokens DataTables keep the desktop table but render opt-in mobile `Item` rows below `@md`.
    **Base UI follow-through (2026-07-12):** replaced the deprecated `@base-ui-components/react` RC with current
    `@base-ui/react`; migrated the low-risk shared Progress, Separator, and Tabs primitives; preserved decorative
    separator semantics and Radix's automatic keyboard tab activation; removed the three superseded Radix
    packages. Remaining primitives are intentionally opportunistic: dialogs/sheets, menus/popovers/tooltips,
    selects/checkboxes, scroll areas, and `asChild` wrappers have different Base UI composition or DOM contracts
    and require focused interaction/browser verification when their owning screen is next touched, not a blind
    bulk swap. **Still TODO:** none for the nova list→`Item` rollout called out here; the design-input queue above
    owns the remaining screen decisions. **Test data:** `UserSeeder`
    (`db:seed --class=UserSeeder`, `SEED_USER=` override) seeds account
    SSH keys/API tokens + a server's IPAM addresses. **Note:** server-settings tabs (SSH keys/DNS/disks/boot-order)
    read live Proxmox, not the DB — they only populate against a reachable node, not via seeders.
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
    node. **Update 2026-07-12:** node **Overview** is built and live-verified with PVE status,
    configured allocation, hardware/topology, root filesystem, PVE/kernel/boot, and endpoint details;
    node **Servers** now uses the existing admin servers API with `filter[node_id]`, preserving search/pagination
    and power actions; node **Settings** now edits node metadata/connection/specs with update-specific optional
    token rotation fields; node **IPAM** now uses a new address-block-group `filter[node_id]` backed by
    `AddressBlockGroup::networkInterfaces()` and renders the node-scoped block-group table with mobile `Item`
    rows. Focused backend filter test and PHPStan, `ddev npm run tc`, type/Wayfinder generation, and
    `ddev npm run build` green on the documented sandbox retry. Authenticated Playwright desktop + 390px mobile
    passed with the node filter present on the API request, no console errors, and no horizontal overflow;
    screenshots: `/tmp/opencode/node-ipam-{desktop,mobile}.png`. The built node sub-pages for reference are
    `overview`, `servers`, `network`, `storages`, `ipam`, and `settings`.
    Reference spec (Vercel style, maintainer prefers it **over** Cloudflare; 4 screenshots pasted
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
  **Root cause + tinker-specific fix now documented in [docker-sandbox.md](docker-sandbox.md)** (2026-07-10):
  the tinker segfault is PsySH's forking-eval loop under the sandbox's virtualized kernel — *not* `pcntl_fork`
  itself (a bare fork loop runs clean) — so the sandbox-local, **uncommitted** fix is `usePcntl => false` in the
  web container's `~/.config/psysh/config.php`. Most "tinker broke" moments are actually shell-quoting errors
  (a `ParseError`/exit 1), not the exit-139 segfault. That doc also records that in the sandbox the agent is
  free to install/run whatever it needs (throwaway + isolated), pointed to from AGENTS.md.
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
