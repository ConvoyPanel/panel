# Bandwidth Rate-Limiting Rework — Implementation Plan

Tracking issue: [#108 "Bandwidth speed limiter"](https://github.com/ConvoyPanel/panel/issues/108)

Status: **Design approved (via requirements interview), not yet implemented.**
Audience: whoever picks up the implementation. Read this top to bottom before touching code.

---

## 1. Why

Today "rate limiting" is a single **binary** behavior buried in
`ServerRateLimitsSyncService`: if a server's monthly `bandwidth_usage` meets or
exceeds its `bandwidth_limit`, every NIC is throttled to `rate=1`; otherwise the
rate limit is removed entirely. There is:

- **No persistent speed cap** (a plan/QoS ceiling that always applies) — which is
  literally what issue #108 asks for ("like Proxmox").
- **No configurability** of the overage penalty (hardcoded `1`).
- **A calendar-month reset** (`date('d') === '01'`) that ignores when a server was
  actually created / billed.
- **A latent unit bug** and a **latent "unlimited" bug** (see §7).

This rework splits the tangled concept into two clean ones — a **persistent speed
cap** and a **configurable overage penalty** — and fixes the reset timing.

## 2. Current state (files to know)

| Concern | File |
| --- | --- |
| NIC `rate`/`link_down` DTO | `app/Data/Server/Proxmox/Config/NetworkDeviceData.php` |
| Byte-scaling helper | `app/Support/ByteUnit.php` |
| MiB↔bytes cast for `rate` | `app/Extensions/Spatie/Data/Proxmox/Casts/RateLimitCast.php` |
| Writes `rate` to all NICs | `app/Services/Servers/ServerNetworkBandwidthService.php` |
| **Overage enforcement (core)** | `app/Services/Nodes/ServerRateLimitsSyncService.php` |
| Enforcement job / command | `app/Jobs/Node/SyncServerRateLimitsJob.php`, `app/Console/Commands/Server/UpdateRateLimitsCommand.php` |
| Usage accounting | `app/Services/Nodes/ServerUsagesSyncService.php` |
| Monthly reset | `app/Console/Commands/Server/ResetUsagesCommand.php` |
| Scheduler (both **commented out**) | `routes/console.php` (lines ~40, ~43) |
| Server model (rules/casts) | `app/Models/Server.php` |
| Node model (rules/casts) | `app/Models/Node.php` |
| Create wiring | `app/Services/Servers/ServerCreationService.php`, `app/Http/Requests/Admin/Servers/StoreServerRequest.php` |
| Build/settings wiring | `app/Http/Requests/Admin/Servers/Settings/UpdateBuildRequest.php` |
| Node update wiring | `app/Http/Requests/Admin/Nodes/UpdateNodeRequest.php` |
| Admin node settings UI | `resources/scripts/routes/_app/admin/nodes.$nodeId/settings.lazy.tsx`, `resources/scripts/features/nodes/api.ts` |

> Note: because both scheduler lines are commented out, enforcement and reset are
> **not currently running**. Enabling them is part of this work (§8).

## 3. Two concepts (the mental model)

1. **Persistent speed cap** — a per-server QoS ceiling. Always enforced *while the
   server is under quota*. Unset ⇒ unlimited. This is the #108 feature.
2. **Overage penalty** — what happens *when `usage ≥ limit`*. An enum action, not a
   constant. Resolved through a cascade (§5).

**Precedence (decided):** *cap normally, penalty on overage.* Per sync run:

```
if server is over quota (and quota is not "unlimited"):
    apply resolved OVERAGE PENALTY   # throttle-to-N  OR  disconnect
else:
    apply persistent SPEED CAP       # or clear the limit if cap is unset
```

The overage penalty **always wins** while over quota (it is *not* "lower of the
two"). When the quota resets (§6), usage returns to 0 and the next sync run
naturally restores the speed-cap branch.

## 4. Scope

**In scope**
- Settings foundation: adopt `spatie/laravel-settings` (P0), host the overage
  global default in a `BandwidthSettings` group (§5.3).
- Persistent per-server speed cap (create + admin build page, applied to all NICs).
- Configurable overage penalty: `throttle` (to a configurable rate) or `disconnect`
  (`link_down=1`), resolved via server → node → global cascade.
- Per-server quota reset anchored to day-of-month (default from `created_at`).
- Correctness fixes: MiB→MB cast, and "unlimited" (`-1`) handling.
- Admin-only UI on the relevant surfaces.

**Out of scope (seams left, not built)**
- Client/end-user configuration (admins only).
- Admin **Settings** screen to edit the global default in-UI — the settings store
  lands now, but its editing UI is a follow-up (§5.3); node/server overrides are
  UI-editable in this plan.
- Paymenter integration — the reset anchor and the cascade are designed so a plan
  can later drive them, but no Paymenter code here.
- Quota pre-limit alerts, usage history/graphs, per-NIC caps.

## 5. The overage penalty & its cascade

### 5.1 Value shape

An overage penalty is a small value object:

```
action: 'throttle' | 'disconnect'
rate:   int|null    # bytes/s, required when action = throttle, ignored otherwise
```

- `throttle` → set NIC `rate` to `rate` (min enforced, see §7.1).
- `disconnect` → set NIC `link_down = 1` (NIC still exists in the guest, no
  carrier; fully reversible). When restoring, clear `link_down`.

### 5.2 Resolution order (server → node → global)

```
resolveOveragePenalty(server):
    return server.overage_penalty            # nullable JSON column on servers
        ?? server.node.overage_penalty       # nullable JSON column on nodes
        ?? BandwidthSettings::overage         # global default (spatie/laravel-settings, see §5.3)
```

Introduce `App\Services\Servers\OveragePenalty` (a `Spatie\LaravelData\Data`
object) and a resolver (e.g. `OveragePenaltyResolver::for(Server $server)`). Keep
the resolver the single source of truth so the sync service, the UI "effective
value" display, and tests all agree.

### 5.3 Where the global default lives — **`spatie/laravel-settings`** (decided)

There is **no persistent settings store** in this codebase today (settings live in
`config/*.php`). We adopt **`spatie/laravel-settings`** as the general, DB-backed,
UI-editable settings foundation, and the overage global default becomes its first
tenant. This is landed as **P0** (§13), *before* the rate-limit work, so the rest
builds on it with no throwaway config stopgap.

Why this over a config/env value or a hand-rolled key-value table:
- Matches the Spatie-heavy stack (`laravel-data`, `typescript-transformer`, …).
- Typed settings **classes**, not stringly-typed keys; composes with the DTO/TS
  tooling already in use.
- **Read cost is a non-issue:** with `SETTINGS_CACHE_ENABLED=true` (we already run
  Redis + Horizon) a cache hit makes **zero** repository/DB calls; the cache
  auto-invalidates on `save()`. Cold cache is a single indexed query per group,
  resolved once per request (singleton). So reads behave like `config()`, but the
  values remain DB-sourced and admin-editable.
- Generalizes to the inevitable next global settings (default limits, branding,
  SMTP, feature flags) with per-property encryption available (`#[ShouldBeEncrypted]`).

Only the **global tier** uses settings; node/server overrides remain DB columns
(§9) because `spatie/laravel-settings` models global singletons, not per-row values.

Settings class:

```php
// app/Settings/BandwidthSettings.php
use Spatie\LaravelSettings\Settings;

class BandwidthSettings extends Settings
{
    // Overage penalty applied when a server exceeds its monthly quota, unless
    // overridden per-node or per-server (§5.2). rate is bytes/s (used when
    // action = 'throttle'). PHP defaults guard against MissingSettings pre-migration.
    public string $overage_action = 'throttle';   // 'throttle' | 'disconnect'
    public int $overage_rate = 1_000_000;         // 1 MB/s

    public static function group(): string
    {
        return 'bandwidth';
    }
}
```

Settings migration (own lane, `database/settings/`):

```php
// database/settings/xxxx_xx_xx_create_bandwidth_settings.php
public function up(): void
{
    $this->migrator->add('bandwidth.overage_action', 'throttle');
    $this->migrator->add('bandwidth.overage_rate', 1_000_000); // bytes/s
}
```

The resolver in §5.2 reads `app(BandwidthSettings::class)` and maps
`{overage_action, overage_rate}` into the same `OveragePenalty` data object it
builds from the node/server JSON columns, so all three tiers produce one uniform
type. Enable caching in `config/settings.php` (`'cache' => ['enabled' =>
env('SETTINGS_CACHE_ENABLED', true)]`).

> Follow-up (not in this plan): a small admin **Settings** screen to edit the
> global default in-UI. Until then the value is seeded/edited via the settings
> migration or Tinker; node/server overrides are already UI-editable.

## 6. Quota reset rework

**Decision:** reset per-server on its own **day-of-month anchor**, defaulting to
`created_at`'s day, stored as a column so Paymenter can later override it.

- Add `servers.bandwidth_reset_day` (`unsignedTinyInteger`, 1–31), backfilled from
  `DAY(created_at)` in the migration.
- Rewrite `ResetUsagesCommand` (`servers:reset-usages`) to run **daily** and reset
  only servers due **today**, with month-length clamping:

  ```
  today = day-of-month(now)
  isLastDayOfMonth = now->day == now->daysInMonth
  reset servers where bandwidth_reset_day == today
     OR (isLastDayOfMonth AND bandwidth_reset_day > now->daysInMonth)   # e.g. anchor=31 in Feb
  ```

- Keep the reset as a single bulk `UPDATE ... SET bandwidth_usage = 0` filtered by
  the above (no per-server queries).
- Schedule it `->daily()` (see §8). Uncomment + switch from the old monthly guard.

> Seam for Paymenter: later, set `bandwidth_reset_day` from the subscription renewal
> day instead of `created_at` — no schema change needed.

## 7. Correctness fixes (fold into the rewrite, not separate features)

### 7.1 Units: MiB → MB
`RateLimitCast` uses `ByteUnit::Mebibytes` (binary, 1024²). The PVE API
(`docs/pve-api/endpoints.ndjson`) documents `net[n].rate` as *"Rate limit in mbps
(megabytes per second) as floating point number"* — i.e. **decimal MB (10⁶)**.
Fix the cast to convert bytes/s ↔ **decimal megabytes** so an admin entering "100"
gets 100 MB/s, not ~95.4. Keep storing bytes/s internally. Add/adjust unit tests.

Also respect the schema `minimum` of `1` (MB/s): when throttling, never emit `< 1`.
`0` disables the limit in Proxmox — treat "clear the cap" as removing the `rate`
key (as `removeRateLimit` already does), not emitting `0`.

### 7.2 "Unlimited" (`-1`) bug
Current condition `usage >= limit && isset(limit)` throttles **unlimited** servers,
because unlimited is stored as `-1` (migration `2024_10_10_033133`) and
`usage >= -1` is always true. The new over-quota check must treat `bandwidth_limit
<= 0` (i.e. `-1`) as **never over quota**. Centralize as `Server::isOverBandwidthQuota()`
so the sync service and any UI badge share one definition.

## 8. Scheduler

In `routes/console.php`, replace the two commented lines with:

```php
Schedule::command(ResetUsagesCommand::class)->dailyAt('00:05');
Schedule::command(UpdateRateLimitsCommand::class)->everyTenMinutes();
```

(Confirm the app's scheduler/cron is actually invoked in deployment; if Horizon-only
today, ensure `schedule:run` is wired.)

## 9. Data model changes (migrations)

1. `servers`
   - `speed_limit` `unsignedBigInteger` nullable — persistent cap, **bytes/s**, null = unlimited.
   - `overage_penalty` `json` nullable — per-server override (`{action, rate}`), null = inherit.
   - `bandwidth_reset_day` `unsignedTinyInteger` — 1–31, backfill `DAY(created_at)`.
2. `nodes`
   - `overage_penalty` `json` nullable — per-node override, null = inherit.

Model updates:
- `Server`: add `$validationRules` for `speed_limit` (`nullable|integer|min:0`),
  `overage_penalty` (nested/`nullable|array` + rule), `bandwidth_reset_day`
  (`sometimes|integer|min:1|max:31`); cast `speed_limit` via `StorageSizeCast`,
  `overage_penalty` → the `OveragePenalty` data cast; add `isOverBandwidthQuota()`.
- `Node`: same `overage_penalty` cast + validation entry. (`$guarded = ['id', ...]`
  so mass assignment is already open.)

## 10. Backend service changes

### 10.1 `ServerNetworkBandwidthService` — ✅ done
Reworked around a single `apply(Server, ?int $rate, ?bool $linkDown = null)` that
reads the config once and re-emits only the NICs that actually differ (so it's
idempotent and skips the PVE write entirely when nothing changed). `$linkDown`
is tri-state: `true` disconnects, `false` reconnects, `null` leaves link state
untouched; a `false` clears `link_down` by omitting the key rather than emitting
`0`. `setRateLimit`/`removeRateLimit` remain as thin wrappers. Clearing a cap
removes the `rate` key.

### 10.2 `ServerRateLimitsSyncService` → per-server `sync(Server)` + job fan-out — ✅ done
**Architecture change (was one node-level loop):** the sync is now fanned out
one job per server, mirroring the existing `BatchSyncNetworkSettingsJob` /
`SyncNetworkSettingsJob` pattern:

- `App\Jobs\Server\SyncServerRateLimitJob` (`Batchable`, `tries = 3`,
  `#[WithoutRelations] Server`, middleware `SkipIfBatchCancelled` +
  `WithoutOverlapping((string) server->id)`) → calls `sync($server)`.
- `UpdateRateLimitsCommand` dispatches **one `Bus::batch` per node** of that
  node's per-server jobs (`->allowFailures()`), so servers reconcile concurrently
  with per-server retry/isolation and stay observable in Horizon.
- The old `App\Jobs\Node\SyncServerRateLimitsJob` (node-level loop) is **deleted**.

`sync(Server)` implements §3 precedence and lets exceptions propagate (the job,
not the service, owns retry) — a transient stale-digest `ConfigModifiedException`
just retries:

```php
if (! $server->isOverBandwidthQuota()) {
    $this->service->apply($server, $server->speed_limit, linkDown: false); // cap + connect
    return;
}
$penalty = $this->resolver->for($server);
$penalty->isDisconnect()
    ? $this->service->apply($server, $server->speed_limit, linkDown: true)
    : $this->service->apply($server, $penalty->rate ?? $server->speed_limit, linkDown: false);
```

Note: `ConfigModifiedException` extends `ConflictHttpException`, **not**
`RequestException` — the old node loop's `catch (RequestException)` never actually
caught the stale-digest case. Per-server retries make this moot.

Future refinement (not done): per-node concurrency is currently bounded only by
Horizon worker count; a per-node throttle could be added if PVE gets hammered.

### 10.3 Create / update wiring
- `StoreServerRequest`: add `limits.speed_limit` (nullable). `ServerCreationService`:
  map `limits.speed_limit` → `speed_limit`. Set `bandwidth_reset_day` from
  `now()->day` (or `created_at`) at creation.
- `UpdateBuildRequest`: add `speed_limit` and the per-server `overage_penalty`
  override alongside the existing `bandwidth_limit` / `bandwidth_usage`.
- `UpdateNodeRequest`: add the per-node `overage_penalty` override.

## 11. Frontend (admin-only)

Mirror existing patterns (typed controller client + TanStack Router lazy routes).

- **Server build page** (extend the existing build settings form / the request that
  feeds `UpdateBuildRequest`; note the server-facing route lives under
  `resources/scripts/routes/_app/servers.$serverUuid/…`): add a **Speed cap** input
  (unit MB/s, empty = unlimited) and an **Overage penalty** control (action select +
  conditional rate input) with an explicit **"Inherit from node/global"** state.
- **Node settings page** (`nodes.$nodeId/settings.lazy.tsx` + `features/nodes/api.ts`):
  add the same overage-penalty control with an **"Inherit from global"** state.
- **Effective value hint:** each override control should show the resolved effective
  value when set to inherit (call/derive from the resolver) so admins see what's
  actually in force.
- Unit handling: display **MB/s**, submit MB/s; backend stores **bytes/s** (decimal).

## 12. Testing

- **Unit:** `RateLimitCast` round-trips decimal MB (100 MB/s ⇄ 100_000_000 bytes);
  min-`1` clamp; clear = remove key.
- **Unit:** `OveragePenaltyResolver` cascade — server override wins, then node, then
  config default; unset levels fall through.
- **Unit:** `Server::isOverBandwidthQuota()` — `-1`/`0` limit ⇒ never over; `usage ==
  limit` ⇒ over; `usage < limit` ⇒ under.
- **Feature:** `ServerRateLimitsSyncService` — under quota applies cap / clears;
  over quota with `throttle` sets rate; over quota with `disconnect` sets
  `link_down`; crossing back reconnects + restores cap. Fake the config repo.
- **Feature:** `ResetUsagesCommand` — resets only servers whose anchor is today;
  month-end clamp for anchor 29–31 in short months; leaves others untouched.
- **Feature/HTTP:** create + build + node-update requests validate and persist the
  new fields.

## 13. Suggested phasing / checklist

- [x] **P0 — Settings foundation (§5.3):** ✅ done. `spatie/laravel-settings` 3.9.0
      added; `config/settings.php` published with caching default-on;
      `App\Settings\BandwidthSettings` + `database/settings/*_create_bandwidth_settings.php`
      (defaults: `throttle` / 1 MB/s); `SETTINGS_CACHE_ENABLED` documented in
      `.env.example` and **disabled in `phpunit.xml`** (the settings cache leaks
      across `RefreshDatabase` tests). Verified by `tests/Unit/Settings/BandwidthSettingsTest.php`.
      Note: `tests/Feature/Servers/ServerDiskManagementTest.php` SIGSEGVs in the ddev
      PHP image — **pre-existing, unrelated** (crashes with our migration removed too).
- [x] **P1 — Model & correctness** ✅ done. Migration (servers.speed_limit bytes/s,
      servers.overage_penalty json, servers.bandwidth_reset_day, nodes.overage_penalty);
      `OveragePenaltyAction` enum + `OveragePenaltyData` + `OveragePenaltyCast` +
      `OveragePenaltyResolver` (reads `BandwidthSettings`); `RateLimitCast` decimal-MB
      fix; `Server::isOverBandwidthQuota()` + `bandwidthResetDay()`. Note: `speed_limit`
      is a **plain bytes/s bigint (no `StorageSizeCast`)** — `StorageSizeCast` is binary
      1048576, which would reintroduce the MiB/MB skew this phase fixes.
- [x] **P2 — Enforcement rewrite** ✅ done. Unified `apply()`; per-server
      `sync(Server)`; **fanned out via `SyncServerRateLimitJob` batched per node**
      (see §10.2 — architecture change from the node-level loop). Old
      `SyncServerRateLimitsJob` deleted. Service + command tests.
- [x] **P3 — Reset rework** ✅ done. `ResetUsagesCommand` rewritten to a daily
      anniversary sweep (`COALESCE(bandwidth_reset_day, day-of-created_at)`, last-day
      clamp). Scheduler in `routes/console.php` **uncommented** — `sync-usages` /5m,
      `reset-usages` daily 00:05, `sync-rate-limits` /10m (usage sync enabled too, or
      quotas never trip). Command test with frozen time.
- [x] **P4 — Create/update wiring** ✅ done. `StoreServerRequest.limits.speed_limit`
      + `ServerCreationService` maps `speed_limit` and sets `bandwidth_reset_day`;
      `UpdateBuildRequest` gains `speed_limit` + nested `overage_penalty`;
      `UpdateNodeRequest` gets `overage_penalty` for free (Node rule spread). Also
      **removed a pre-existing dead `backup_limit` line** in `UpdateBuildRequest`
      (referenced a non-existent rule key + phantom column; surfaced as a fatal in the
      test env). Creation + node-HTTP tests. (`updateBuild` full HTTP path is not
      unit-tested — its `buildModificationService`/firewall Proxmox coupling has no
      test-fake harness, a pre-existing gap; persistence is covered via the node HTTP
      test + creation test, which use the identical mass-assign+cast path.)
- [~] **P5 — Frontend — DEFERRED (decision: backend-only for now).** No UI built:
      the server create/edit surface is mid-overhaul and there's no "edit build/limits"
      admin page yet. The full frontend TODO (create-wizard speed-cap field, per-server
      override once an edit page exists, per-node overage control on node settings, and
      an eventual global-settings screen) is captured in
      [`docs/v10-next-handoff.md`](v10-next-handoff.md) under "open product follow-ups".
      Endpoints are fully functional via API/Tinker in the meantime.
- [ ] **P6 — Docs/changelog** and follow-up tickets: an admin **Settings** screen to
      edit `BandwidthSettings` in-UI (§5.3), and Paymenter integration.

## 14. Risks / watch-outs

- **Scheduler not running today** — verify cron/`schedule:run` is actually invoked
  in production before assuming enforcement is live.
- **`disconnect` is a hard penalty** — the guest sees carrier-down; make the admin
  UI copy explicit and keep it reversible (never drop the NIC, only `link_down`).
- **Digest races** — `ProxmoxConfigRepository::update` uses the config `digest`;
  the per-server `try/catch` already tolerates a stale-digest `RequestException`
  (next sync run corrects it). Preserve that.
- **`StorageSizeCast` semantics** — confirm it stores/returns bytes as expected for
  `speed_limit` (it's already used for `bandwidth_limit`/`bandwidth_usage`).
- **Global default not UI-editable under option (A)** — set expectations; overrides
  cover most needs.
