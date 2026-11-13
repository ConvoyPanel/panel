# Telemetry

Once a day the panel sends an anonymous usage report to the Convoy console at
`convoypanel.com`. It shows how many installations exist, how big they are,
which versions they run, and which features and pages get used. It is the only
analytics the panel has. The panel loads no third-party scripts.

This file is the contract between the panel and the console. The console's
request validation (`StoreTelemetryReportRequest`) points here, and
`tests/Support/TelemetryContract.php` holds the panel to the same rules.

## What is never sent

- Names, email addresses, IP addresses, hostnames, FQDNs or URLs, including
  `APP_URL`.
- The license key, `APP_KEY`, or anything derived from them.
- Anything typed into the panel: server names, descriptions, audit log
  properties, settings values.
- Which account did anything. Counts are totals for the whole installation.

The report holds only numbers, `true`/`false` switches, version numbers cut
down to their digits, a fixed slug for the database engine, and a random
installation id. The console stores a country it looks up from the address the
report came from, and does not store the address itself.

## Turning it off

- **Admin → Settings → Telemetry.** Switching it off stops the reports and asks
  the console to erase every report it holds for this installation (`DELETE`,
  retried for about seven hours if the console can't be reached). It also
  replaces the installation id and clears the page-view counters, so switching
  it back on later starts a new installation that reports nothing from the time
  it was off. "View report" on the same screen shows the exact JSON the next
  report would send, built by the same code as the report.
- **`CONVOY_TELEMETRY_ENABLED=false`** stops the reports and the page-view
  counting. The admin switch then shows as off and can't be turned on.
- **`CONVOY_TELEMETRY_URL=`** (empty) does the same, for an install that can't
  reach the internet.

The environment switches don't contact the console, so they don't erase
anything already sent. Use the admin switch for that.

## When it is sent

The scheduler checks every minute, but each installation reports at its own
minute of the UTC day, taken from its installation id. This spreads the
console's load over 24 hours. At most one report goes out per UTC day.

A report that fails (a timeout, an error status, a refusal) is logged as a
warning and is not retried. The next day's report covers everything since the
last report the console accepted, so nothing is lost or counted twice.

## The request

```
POST https://convoypanel.com/api/v1/telemetry
Accept: application/json
Content-Type: application/json
User-Agent: Convoy/<panel version>
```

The console answers `204` when it stores the report, `422` with validation
errors, and `429` above 60 requests an hour from one address. A second report
for the same UTC day replaces the first.

```
DELETE https://convoypanel.com/api/v1/telemetry/{installation}
```

always answers `204` and erases that installation and its reports.

The connect timeout is 5 seconds and the whole request has 10.

## The report

```json
{
    "schema": 1,
    "installation": "b2f2dda1-5a66-498b-b410-1d8f57f03024",
    "panel": { "version": "5.0.2", "php": "8.4.12", "database": "pgsql", "database_version": "17.2" },
    "users": {
        "total": 340,
        "admins": 3,
        "active": { "day": 40, "week": 120, "month": 210 },
        "active_admins": { "day": 2, "week": 3, "month": 3 }
    },
    "nodes": [{ "proxmox": "8.4.1", "agent": "1.2.0", "cpus": 64, "memory_gib": 256, "servers": 80 }],
    "resources": { "backups": 410, "locations": 3 },
    "features": { "mail": true, "oauth.github": false },
    "activity": { "auth.login.succeeded": 55 },
    "views": { "servers.show": 300 }
}
```

General rules:

- Every count is a whole number from 0 to 10,000,000.
- Keys in `resources`, `features`, `activity` and `views` match
  `^[a-z0-9]+(?:[._-][a-z0-9]+)*$`, are at most 120 characters, and a map has
  at most 200 keys. `features` values are booleans; the others are counts.
- These are the console's limits, and one value past them gets the whole
  report refused. The panel caps a count at 10,000,000 and leaves keys past
  the 200th out rather than send a report the console would refuse.
- A version starts with `major.minor` digits. The panel sends only the digits
  (`8.4.12`, never `8.4.12-1ubuntu2`), and the console keeps only the digits.
- The console drops fields it doesn't know. New fields and keys are additive.
  `schema` changes only for a breaking change.

### `schema`, `installation`

`schema` is `1`. `installation` is a random UUID generated when the panel is
installed or upgraded, and replaced when an admin turns telemetry off. It is
stored in the panel's settings table and is not derived from `APP_KEY`,
`APP_URL` or the license.

### `panel`

| Field | Meaning |
| --- | --- |
| `version` | `config('app.version')`: a release (`5.0.2`, `5.1.0-rc.2`) or `canary` for a source build. Anything else, such as a fork's own version string, is sent as `other`. |
| `php` | The running PHP version. |
| `database` | `pgsql`, `mysql`, `mariadb` or `sqlite`. MariaDB reached through the `mysql` driver is reported as `mariadb`. |
| `database_version` | The database server's version, or `null` if it can't be read. |

### `users`

| Field | Meaning |
| --- | --- |
| `total` | Accounts. |
| `admins` | Accounts with `root_admin`. |
| `active.day` / `.week` / `.month` | Distinct accounts with a signed-in browser request in the last 1, 7 and 30 days. |
| `active_admins.*` | The same, for admins. |

Activity is read from `users.last_active_at`, which the `RecordSessionActivity`
middleware updates at most once a minute per account. Requests made with an API
token don't count: a script calling the API is not someone using the panel.
Distinct counts don't add up across days, so each window is computed and sent
on its own.

### `nodes`

One entry per node, at most 500: a panel with more nodes reports its first
500 by id. Each field has its own cap: `cpus` 10,000, `memory_gib` 1,000,000
and `servers` 100,000.

| Field | Meaning |
| --- | --- |
| `proxmox` | The Proxmox VE version the host reported when its Anchor enrolled, or `null` for a node added without an Anchor. The report never calls Proxmox. |
| `agent` | The Anchor agent version, or `null` without an agent. |
| `cpus` | The node's CPU count, as configured in the panel. |
| `memory_gib` | The node's memory in GiB, rounded. |
| `servers` | Servers on the node. The total server count is the sum of these, so it isn't repeated in `resources`. |

### `resources`

Counts of things that exist right now.

| Key | Meaning |
| --- | --- |
| `address_blocks` | IP address blocks in IPAM. |
| `addresses.assigned` | IP addresses assigned to a server. |
| `addresses.ipv4` | IPv4 addresses in IPAM. |
| `addresses.ipv6` | IPv6 addresses in IPAM. |
| `api_tokens.account` | API tokens accounts created for themselves. |
| `api_tokens.application` | Application API tokens. |
| `backups` | Server backups. |
| `clusters` | Proxmox clusters the panel knows about. |
| `image_definitions` | Image definitions (operating system images). |
| `image_versions` | Versions across all image definitions. |
| `invites.pending` | Account invitations that haven't expired. |
| `isos` | ISOs in the library. |
| `locations` | Locations. |
| `passkeys` | Registered passkeys. |
| `relays` | Enrolled Anchor relays. |
| `server_presets` | Server presets. |
| `ssh_keys` | SSH keys saved to accounts. |
| `storages` | Storages. |
| `users.oauth` | Accounts linked to an OAuth provider. |
| `users.passkeys` | Accounts with at least one passkey. |
| `users.two_factor` | Accounts with two-factor authentication confirmed. |
| `vlans` | VLANs. |

### `features`

Switches that describe how the installation is set up.

| Key | `true` when |
| --- | --- |
| `accounts.avatar_change` | Accounts may change their own profile picture. |
| `accounts.email_change` | Accounts may change their own email address. |
| `accounts.name_change` | Accounts may change their own display name. |
| `accounts.password_change` | Accounts may change their own password. |
| `anchor.agents` | At least one node runs an enrolled Anchor agent. |
| `anchor.panel_url` | A panel URL for Anchors is set in the settings. |
| `anchor.relays` | At least one Anchor relay is enrolled. |
| `artifacts.s3` | Uploaded disk images and ISOs are stored on S3. |
| `audit.pruning` | Old audit log entries are pruned (`APP_AUDIT_PRUNE_DAYS`). |
| `audit.reveal_staff_identity` | Clients see which staff member acted in the audit log. |
| `avatars.s3` | Profile pictures are stored on S3. |
| `bandwidth.overage_disconnect` | A server over its bandwidth quota is disconnected, not throttled. |
| `mail` | The panel can send mail. |
| `metrics` | VictoriaMetrics is configured. |
| `oauth.github` | Sign-in with GitHub is enabled and configured. |
| `oauth.gitlab` | Sign-in with GitLab is enabled and configured. |
| `oauth.google` | Sign-in with Google is enabled and configured. |
| `oauth.oidc` | Sign-in with an OpenID Connect provider is enabled and configured. |
| `oauth.registration` | Signing in with an OAuth provider can create a new account. |

Only the OAuth providers the panel ships are reported. A provider an operator
added to `config/oauth.php` is never named.

### `activity`

Audit log entries recorded since the last report the console accepted, counted
by event. The keys are `AuditEvent` values (`app/Enums/Audit/AuditEvent.php`),
such as `auth.login.succeeded` or `server.backup.created`. Only values the
catalog still has are counted, and nothing else from the entry is read: not
who acted, not the subject, not the properties.

The first report after an upgrade, or after an admin turns telemetry back on,
counts from that moment. Each later one counts from the end of the last
accepted report, so an entry is counted in exactly one report.

Audit retention can't drop an entry before it is counted in normal operation.
The pruner deletes entries older than `APP_AUDIT_PRUNE_DAYS` (at least 1), and
a report counts entries at most a day old. Entries can be missed only when
reports fail for longer than the retention period. Events marked as kept
forever are never pruned.

### `views`

Page views since the last report the console accepted, keyed by the SPA's
route ids.

The panel is a client-side app, so the server never sees a page change. The
browser counts each finished navigation inside the signed-in app by its route
id (`resources/scripts/features/telemetry/page-views.ts`). It doesn't count a
hover prefetch, a change to the search parameters alone (such as paging a
table), a re-render of the same page, or the sign-in screens. While telemetry
is off, the browser counts nothing and sends nothing.

Every five minutes, and when the tab is hidden, the browser posts its counts to
`POST /api/client/telemetry/page-views` with `fetch` and `keepalive`, so the
last batch survives the tab closing. The body is nothing but
`{"<route id>": <count>}`. A batch that fails is dropped, not retried. The
endpoint takes only signed-in browser sessions, never an API token, and at most
ten batches a minute from one account. A batch may hold up to 50 pages, and it
adds at most 20 to any one page. Nothing about the sender is stored.

The key is the route id with the pathless layouts, groups and parameters taken
out, and the remaining parts joined with dots. A key never carries a value from
the URL, nor which parameter was there. A page that ends in a parameter is a
detail page, `show`. Any other index route ends in `index`:

| Route id | Key |
| --- | --- |
| `/_app/_dashboard/` | `index` |
| `/_app/servers/$serverUuid/` | `servers.show` |
| `/_app/servers/$serverUuid/backups` | `servers.backups` |
| `/_app/(fullscreen)/servers/$serverUuid/console` | `servers.console` |
| `/_app/admin/_dashboard/nodes/` | `admin.nodes.index` |
| `/_app/admin/nodes/$nodeId/` | `admin.nodes.show` |
| `/_app/admin/nodes/$nodeId/settings` | `admin.nodes.settings` |

Route ids come from the browser, so the panel accepts only the ids in the
route tree that TanStack Router generates at build time
(`resources/scripts/routeTree.gen.ts`, which `npm run build`, the Docker image
and the release archive all contain) and drops the rest. A user can't put free
text into the report through this. Without a built route tree, no views are
counted. `PageViewsTest` checks that no two pages in the built tree share a key.

The counters live in the cache. A report reads them without resetting them,
and only once the console has accepted it does the panel take the sent amounts
back off, so a view recorded while a report is in flight is counted the next
day.

## Changing the report

Add a key to the builder (`app/Services/Telemetry/TelemetryReportBuilder.php`)
and document it here. `ReportTest` fails for a resource or feature key missing
from this file. Change `schema` only for a change an older console can't
read, and change the console first.
