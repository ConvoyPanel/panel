# Audit logging — plan

Covers GitHub #53 (client-side audit logging), widened during design to a
panel-wide audit trail: client server actions, account/security events, admin
panel actions, and API-token attribution. Written 2026-08-17.

**All six slices are implemented.** Changes made while building are marked
"revised in build" below.

## The state we are starting from

There is an activity-log system in the tree, ported from Pterodactyl in 2022.
It has never been wired up and it does not work:

- **Nothing calls it.** There are zero `Activity::` call sites outside the
  facade itself. `ActivityController` (`Client/Servers/ActivityController.php`)
  has no route pointing at it.
- **The schema and the code disagree.** Migration
  `2022_11_02_223634_refactor_activity_logs_table` dropped `created_at` /
  `updated_at` in favour of a `timestamp` column, but
  `ActivityLog::prunable()` still filters on `created_at` and
  `ActivityLogData::fromModel()` still reads both. The daily `PruneCommand`
  registered at `routes/console.php:39` therefore throws every night.
- **The writer would fail on first use.** `ActivityLogService::getActivity()`
  mass-assigns `api_key_id`, which is not a column on `activity_logs`.

So this is a rebuild, not a revival. Both tables are empty, which means there is
no data to migrate and no compatibility to preserve.

### Do not confuse this with the PVE task log

`app/Enums/Activity/{Status,TaskStatus,TaskExitStatus}.php`,
`app/Data/Server/Proxmox/Activity/*` and
`app/Services/Proxmox/Server/ProxmoxActivityClient.php` are Proxmox *task*
plumbing that happens to share the word "activity". They are live and unrelated.
Leave them alone.

## Decisions

| Question | Decision |
| --- | --- |
| Foundation | Thin in-house, no `spatie/laravel-activitylog` |
| Event text | Enum key in the DB, copy rendered on the frontend |
| Call sites | Explicit `Audit::record(...)`, backed by a coverage test |
| Client sees staff actions | Per-event `visibility()`; identity masked by an operator setting |
| Retention | Tiered per-event: security forever, ops pruned |
| v1 surfaces | Client server Activity tab + global admin log |

### Why not spatie

Closer than it first looks — its `LogsActivity` auto-diff trait is opt-in, so
"it would log noise" is not a real objection. The actual reason is that we would
customise most of what it provides: its schema has no `ip`, `user_agent` or
token column (a follow-up migration plus a subclassed model), its
`description` column is non-nullable and pointless under enum-key rendering, and
its cleanup command has a single global window where we want tiered retention.
That leaves the `Activity` model's query scopes and a batch UUID helper as the
net gain, which is not worth a dependency to carry across Laravel upgrades.

### Single subject, not many

The Pterodactyl port models subjects as a many-to-many
(`activity_log_subjects`). With panel-wide scope, "subject = the thing acted
on, actor = who acted" covers every case in the list — a user's own events are
reachable by actor, a server's by subject. Drop the join table.

## Schema

One new migration: drop `activity_logs` and `activity_log_subjects`, create
`audit_logs`. The three 2022 migrations stay in the tree untouched.

```
id                bigint
batch             uuid, nullable
event             string                   -- AuditEvent value, e.g. server.power.start
actor_type/id     morph columns, nullable  -- User or SystemActor; null only if unattributable
actor_label       string nullable          -- the actor's display name, copied at write time
api_token_id      foreignId nullable       -- personal_access_tokens, nullOnDelete
subject_type/id   morph columns, nullable  -- Server, Node, User, token, ...
ip                string(45) nullable      -- 45 fits IPv6
user_agent        string(500) nullable
properties        json
created_at        timestamp
```

Rows are immutable, so there is no `updated_at` — set `const UPDATED_AT = null`
on the model rather than disabling timestamps wholesale, so `created_at` is
still managed for us. This is the specific drift that broke the old system;
using the Laravel convention instead of a bespoke `timestamp` column avoids
repeating it.

Indexes: `(subject_type, subject_id, created_at)` for the server tab feed,
`(actor_type, actor_id, created_at)` for per-user views, `(event)` and
`(created_at)` for filtering and pruning. The morph columns are declared by hand
rather than through `nullableNumericMorphs()`, because that helper adds its own
`(type, id)` index which is a strict prefix of the composites above — pure write
overhead on an append-only table.

### actor_label, and why it is denormalised (revised in build)

**Nothing in this panel uses `SoftDeletes`.** The plan originally specified
`morphTo()->withTrashed()` on the actor, copying the old Pterodactyl port — but
that is a no-op here, and it means deleting a user silently anonymises every
action they ever took. An audit log that forgets who acted the moment you delete
the account is not an audit log, and `admin.user.deleted` is retained forever
precisely so that record survives.

So the actor's display name is copied onto the row at write time. The morph stays
(it resolves while the actor exists, and drives the actor filter); `actor_label`
is the snapshot of who they were then. `actor_id` is also still readable after
deletion, so entries by the same removed account remain correlatable.

The alternative — making `User` soft-delete — is a much larger behavioural change
across the panel and is not worth it for this.

## The event catalog

`app/Enums/Audit/AuditEvent.php`, string-backed, dot-namespaced by area:

```php
enum AuditEvent: string
{
    case SERVER_POWER_START = 'server.power.start';
    case SERVER_REINSTALLED = 'server.reinstalled';
    case ACCOUNT_PASSWORD_UPDATED = 'account.password.updated';
    case ADMIN_NODE_DELETED = 'admin.node.deleted';
    // ...
}
```

Case names are `SCREAMING_SNAKE_CASE`, matching every other enum in `app/Enums`.
Values are `area.thing.verb`, or the shorter `area.verb` where there is no
intermediate noun worth naming (`server.renamed`, `auth.logout`).

The catalog was built from the actual route table rather than from imagination:
105 mutating routes under `api/client` and `api/admin`, plus the `api/auth`
endpoints. Note that `api/application/*` is a token-authenticated mirror of
`api/admin/*` served by the **same controllers**, so one call site covers both
surfaces and the actor resolves to a `User` or a `SystemActor` accordingly.

Two metadata methods, each written as "sensible default plus an explicit
exception list" so adding an event needs no thought in the common case:

```php
public function retention(): AuditRetention
{
    return match ($this) {
        self::ACCOUNT_PASSWORD_UPDATED,
        self::ACCOUNT_TWO_FACTOR_DISABLED,
        self::ACCOUNT_API_KEY_CREATED,
        /* ... security events ... */ => AuditRetention::FOREVER,
        default => AuditRetention::STANDARD,
    };
}

public function visibility(): AuditVisibility
{
    return match ($this) {
        // The one exception so far: it reveals that the panel minted a token
        // capable of impersonating the user.
        self::ADMIN_USER_SSO_TOKEN_GENERATED => AuditVisibility::ADMIN_ONLY,
        default => AuditVisibility::CLIENT,
    };
}
```

The enum auto-transforms into an exhaustive TypeScript string union in
`resources/scripts/types/generated.d.ts` (confirmed: `App\Enums\*` enums already
land there without an attribute). Typing the frontend copy map as
`Record<AuditEvent, (props) => ReactNode>` therefore makes a PHP event with no
matching copy a **type error**, closing the "forgot the wording" loop the same
way the coverage test closes "forgot to log it".

## The recorder

`app/Services/Audit/AuditLogger.php`, reached through an `Audit` facade so call
sites stay one line. **Revised in build:** the facade is `Audit`, not `AuditLog`,
so it does not collide with the model of that name — the two would otherwise need
aliasing in every file that touches both.

```php
Audit::record(
    AuditEvent::ServerPowerStart,
    subject: $server,
    properties: ['signal' => 'start'],
);
```

- **Actor** resolves to the explicit argument, else `auth()->user()`, else null.
  Note that `auth()->user()` already returns a `SystemActor` (not a `User`) for
  panel-wide application tokens — see `AdminAuthenticate` and
  `CreateApplicationTokenService` — so the morph must be typed
  `User|SystemActor` and the frontend must render a `SystemActor` actor as
  "System". A null actor should mean *unattributable*, e.g. the scheduler, and
  should be rare enough to be suspicious.
- **Token attribution** reads `$request->user()?->currentAccessToken()?->id`, so
  a leaked key's blast radius is visible without a separate code path.
- **Request metadata** (ip, user agent) is captured automatically and is null in
  console and queue contexts. **Revised in build:** that context check keys off
  whether a route has actually been resolved. Neither `runningInConsole()` nor the
  presence of `REMOTE_ADDR` can tell the difference — outside a real request the
  container still hands back a synthetic `Request` whose `ip()` is `127.0.0.1`,
  and both signals are also true under the test runner. Recording a scheduled
  prune as having come from localhost would be a lie in the audit trail.
- **Never breaks the action.** The write is wrapped in try/catch: rethrow
  outside production, `Log::error` in production. This one behaviour is worth
  keeping from the old port.
- **Batching.** `AuditLog::batch(fn () => ...)` shares one UUID across the rows
  a single user action produces (bulk deletes and the like), with a nesting
  counter. Ported in spirit from `ActivityLogBatchService`, folded into the
  logger.

### Where the call goes relative to the work

Record *after* the action has succeeded, or inside its transaction where one
exists — an action that rolls back must not leave an audit row.

### Queued work logs intent, not outcome

Server operations dispatch jobs (`SendPowerCommandJob`, `ConfigureVmJob`, …).
The audit log records what was **requested**, at the controller, where
attribution is unambiguous: "Eric requested a reinstall". Whether the job then
succeeded is deployment/task tracking, which already exists — see
`docs/deployment-tracking-handoff.md`. Keeping that boundary stops the audit log
from becoming a second, worse job monitor.

## Visibility

Two orthogonal knobs, so neither question has to be answered per deployment
*and* per event:

1. **Is this event ever client-visible?** — `AuditEvent::visibility()`, default
   `Client`. This is a property of the event, decided once when it is added.
2. **Which staff member did it?** — `AuditSettings::$reveal_staff_identity`
   (spatie settings, matching `app/Settings/BandwidthSettings.php`), default
   `false`. Client-facing serialisation renders an admin actor who is not the
   viewer as "Staff"; flipping it on names them. Admin-facing views always show
   the real actor.

Default posture is therefore: clients see *that* staff acted on their server,
not *who*. Internal or small deployments flip one switch in admin settings.

The existing IP rule in `ActivityLogData` is worth carrying over — an actor sees
their own IP, admins see all, everyone else sees none.

## Retention

`PruneAuditLogsCommand`, daily, replacing the currently-broken `PruneCommand`
registration in `routes/console.php`:

- `AuditRetention::FOREVER` events are never deleted.
- Everything else is deleted past `config('audit.prune_days')` (default 90,
  under `APP_AUDIT_PRUNE_DAYS`, replacing `APP_ACTIVITY_PRUNE_DAYS`).
- Deletes chunk (`audit.prune_chunk`, default 1000) so a long-neglected install
  does not issue one enormous statement. By explicit id list, because Postgres
  has no `DELETE ... LIMIT`.

This is a bespoke command rather than Laravel's generic `PruneCommand`, because a
`prunable()` scope cannot express a per-event exemption.

## API and UI

**Client** — `GET /api/client/servers/{server}/audit-logs`, behind the existing
server-access middleware, filtered through the model's
`clientVisible()` scope unless the viewer is an admin. Paginated, sortable by `created_at`.

**Admin** — `GET /api/admin/audit-logs` via `spatie/laravel-query-builder`,
filterable by `event`, actor, subject, `batch` and date range.

`ActivityLogData` is replaced by `AuditLogData` exposing `event` (the key),
`properties`, masked `actor`, conditional `ip`, and `createdAt`.

Frontend: a new `resources/scripts/features/servers/activity/` tab, and
`resources/scripts/features/admin/audit-logs/` for the global table. The tab is
labelled "Activity" for users; everything in code is called `audit` — the route
names are not user-visible, so the internal vocabulary stays consistent.

## The coverage test

`tests/Feature/Audit/AuditCoverageTest.php` enumerates registered
`POST/PUT/PATCH/DELETE` routes in `api-client.php` and `api-admin.php` and
asserts each one's controller file references `AuditEvent::`, or appears in an
explicit `EXEMPT` map with a one-line reason per entry.

Be honest about what this is: a **heuristic guard**, not a proof. It cannot tell
whether the right event fires on the right branch, only that somebody thought
about the endpoint. It exists so a new mutating route fails CI instead of
silently logging nothing, and the exemption list makes "we decided not to log
this" a deliberate, reviewable act.

## Slices

1. **Foundation** *(done)* — migration, `AuditEvent` + `AuditRetention` + `AuditVisibility`,
   `AuditLog` model, `AuditLogger` + facade, `AuditSettings`, prune command,
   fix the broken schedule, delete the old port (`app/Services/Activity/*`,
   `app/Facades/{Activity,LogBatch,LogTarget}.php`,
   `app/Models/ActivityLog{,Subject}.php`, `app/Http/Middleware/Activity/*`,
   `app/Events/Activity/Activity.php`, `app/Data/Activity/*`,
   `app/Providers/ActivityLogServiceProvider.php`, `config/activity.php` —
   **not** the Proxmox task files listed above).
2. **Client + account call sites** *(done)* — server power, reinstall, rename, backups,
   restore, disks, network, firewall, password reset, console; login, logout,
   failed login, password change, 2FA and passkey changes, API key lifecycle,
   session revocation, OAuth connections.
3. **Admin call sites and token attribution** *(done)* — nodes, locations, users,
   presets, templates, IPAM, settings.
4. **Client server Activity tab.** *(done)*
5. **Global admin audit log.** *(done)*
6. **Coverage test and the exhaustive TS copy map.** *(done)*

Slices 1-3 are shippable without any UI; 4 and 5 are what close #53.

## What the build added beyond the plan

- **Authentication is audited from events, not call sites.** Login, logout,
  failed login and the two-factor transitions run through Fortify's own
  controllers, so there is no code of ours to put a `Audit::record()` in.
  `App\Listeners\AuditAuthenticationSubscriber` handles them. Its methods are
  named `on*` rather than `handle*` on purpose: Laravel's event auto-discovery
  claims any `handle*` method taking an event, which registered every listener a
  second time on top of the explicit `Event::subscribe()` and logged every
  sign-in twice.
- **`server.power.sent` carries the command as a property** rather than there
  being a case per signal. `PowerCommand` has seven values and an admin mirror of
  each; enumerating them would mean fourteen cases and a catalog change every
  time it grows.
- **Three auth cases were dropped from the catalog** (`auth.login.passkey`,
  `auth.two-factor.challenged`, `auth.identity.confirmed`). A passkey login, a
  completed two-factor challenge and an identity re-confirmation all end in
  `Auth::login()`, so each would have double-counted one sign-in.
- **The API layer** is `AuditLogData` plus `AuditActorData` / `AuditSubjectData`.
  Actor masking lives in `AuditActorData::forViewer()`, which is also where the
  `reveal_staff_identity` setting is read.
- **Coverage stands at 110 handlers**, deduplicated across `api/client`,
  `api/admin`, `api/application` and `api/auth`. `api/application/*` is a
  token-authenticated mirror of `api/admin/*` served by the **same controllers**,
  so one call site covers both and the actor resolves to a `User` or a
  `SystemActor` accordingly. A second test fails if an exemption stops matching
  any route, so a stale entry cannot sit there quietly excusing a future
  controller that reuses the name.
