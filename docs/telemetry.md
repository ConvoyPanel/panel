# Telemetry

Once a day the panel sends an anonymous usage report to the Convoy console at
`convoypanel.com`. It shows how many panels exist, how big they are, which
versions they run, and which features and pages get used. It is the only
analytics the panel has. The panel loads no third-party scripts.

This file is the contract between the 4.x panel and the console for schema 1.
The console checks reports with `StoreTelemetryReportRequest`, and
`tests/Support/TelemetryContract.php` holds the panel to the same rules.

## What is never sent

- Names, email addresses, IP addresses, hostnames, FQDNs or URLs, including
  `APP_URL`.
- `APP_KEY`, Proxmox API tokens, or anything derived from them.
- Anything typed into the panel: server names, location descriptions,
  template names, settings values.
- Which account did anything. Counts are totals for the whole panel.

The report holds only numbers, `true`/`false` switches, version numbers cut
down to their digits, a fixed slug for the database engine, and a random
installation id. The console stores a country it looks up from the address the
report came from, and does not store the address itself.

## Turning it off

- **Admin → Settings → Telemetry.** Switching it off stops the reports and asks
  the console to erase every report it holds for this panel (`DELETE`, retried
  for about seven hours if the console can't be reached). It also replaces the
  installation id and clears the page-view counters, so switching it back on
  later starts a new installation that reports nothing from the time it was
  off. "View report" on the same screen shows the exact JSON the next report
  would send, built by the same code as the report.
- **`CONVOY_TELEMETRY_ENABLED=false`** stops the reports and the page-view
  counting. The admin switch then shows as off and can't be turned on.
- **`CONVOY_TELEMETRY_URL=`** (empty) does the same, for a panel that can't
  reach the internet.

The environment switches don't contact the console, so they don't erase
anything already sent. Use the admin switch for that. Like any change to
`.env`, they take effect once the configuration cache is rebuilt
(`php artisan optimize`).

The switch is on until an admin changes it. `telemetry.default` in
`config/convoy.php` sets that starting position; `false` makes telemetry
opt-in.

## When it is sent

The scheduler checks every minute. Each panel becomes due at its own minute of
the UTC day, taken from its installation id, which spreads the console's load
over 24 hours. From that minute it stays due until a report has gone out that
UTC day, so a panel that was down at its minute still reports later the same
day.

At most one report goes out per UTC day. A report that fails (a timeout, an
error status, a refusal) is logged as a warning and is not retried until the
next day. The next report carries every page view since the last report the
console accepted, so nothing is lost or counted twice.

## The request

```
POST https://convoypanel.com/api/v1/telemetry
Accept: application/json
Content-Type: application/json
User-Agent: Convoy/<panel version>
```

The console answers `204` when it stores the report, `422` with validation
errors, and `429` when it is rate limiting. A second report for the same UTC
day replaces the first.

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
    "panel": { "version": "4.7.0", "php": "8.2.35", "database": "mysql", "database_version": "8.4.13" },
    "users": {
        "total": 340,
        "admins": 3,
        "active": { "day": 40, "week": 120, "month": 210 },
        "active_admins": { "day": 2, "week": 3, "month": 3 }
    },
    "nodes": [{ "proxmox": null, "agent": null, "cpus": 0, "memory_gib": 256, "servers": 80 }],
    "resources": { "backups": 410, "locations": 3 },
    "features": { "coterm": true },
    "views": { "servers.show": 300 }
}
```

General rules, all set by the console:

- Every count is a whole number from 0 to 10,000,000. The panel caps a count
  at that rather than send one the console would refuse.
- Keys in `resources`, `features` and `views` match
  `^[a-z0-9]+(?:[._-][a-z0-9]+)*$` and are at most 120 characters, and a map
  has at most 200 keys. `features` values are booleans; the others are counts.
- A version starts with `major.minor` digits. The panel sends only the digits
  (`8.2.35`, never `8.2.35-1+deb12`), and the console keeps only the digits.
- The console drops fields it doesn't know. New fields and keys are additive.
  `schema` changes only for a breaking change.

The console also takes an `activity` map. The 4.x panel doesn't send one: it
keeps no catalog of events to count.

### `schema`, `installation`

`schema` is `1`. `installation` is a random UUID created by the migration that
adds telemetry, and replaced when an admin turns telemetry off. It is stored in
the `telemetry_settings` table and is not derived from `APP_KEY`, `APP_URL` or
anything else about the panel.

### `panel`

| Field | Meaning |
| --- | --- |
| `version` | `config('app.version')`: a release (`4.7.0`, `4.7.0-rc.4`) or `canary` for a source build. Anything else, such as a fork's own version string, is sent as `other`. |
| `php` | The running PHP version. |
| `database` | `mysql`, `mariadb`, `pgsql` or `sqlite`. MariaDB reached through the `mysql` driver is reported as `mariadb`. |
| `database_version` | The database server's version, or `null` if it can't be read. |

### `users`

| Field | Meaning |
| --- | --- |
| `total` | Accounts. |
| `admins` | Accounts with `root_admin`. |
| `active.day` / `.week` / `.month` | Distinct accounts with a signed-in browser request in the last 1, 7 and 30 days. |
| `active_admins.*` | The same, for admins. |

Activity is read from `users.last_active_at`, which the `RecordUserActivity`
middleware updates at most once a minute per account. Requests made with an API
token don't count: a script calling the API is not someone using the panel.
Distinct counts don't add up across days, so each window is computed and sent
on its own.

### `nodes`

One entry per node, at most 500.

| Field | Meaning |
| --- | --- |
| `proxmox` | Always `null` on 4.x. The panel keeps no Proxmox version on record, and the report never calls Proxmox. |
| `agent` | Always `null` on 4.x, which has no node agent. |
| `cpus` | Always `0` on 4.x, meaning unknown: a 4.x node records its memory and disk, not its CPU count. |
| `memory_gib` | The node's memory as configured in the panel, in GiB, rounded. |
| `servers` | Servers on the node. The total server count is the sum of these, so it isn't repeated in `resources`. |

### `resources`

Counts of things that exist right now.

| Key | Meaning |
| --- | --- |
| `address_pools` | IP address pools in IPAM. |
| `addresses.assigned` | IP addresses assigned to a server. |
| `addresses.ipv4` | IPv4 addresses in IPAM. |
| `addresses.ipv6` | IPv6 addresses in IPAM. |
| `api_tokens.account` | API tokens accounts created for themselves. |
| `api_tokens.application` | Application API tokens. |
| `backups` | Server backups. |
| `coterms` | Coterms (console proxies). |
| `isos` | ISOs in the nodes' libraries. |
| `locations` | Locations. |
| `ssh_keys` | SSH keys saved to accounts. |
| `template_groups` | Template groups. |
| `templates` | Templates across all groups. |

### `features`

Switches that describe how the panel is set up.

| Key | `true` when |
| --- | --- |
| `activity.pruning` | Old activity log entries are pruned (`APP_ACTIVITY_PRUNE_DAYS` above 0). |
| `backups.pruning` | Backups stuck in progress are failed after a while (`BACKUP_PRUNE_AGE` above 0). |
| `backups.throttle` | Backup creation is rate limited per user (`BACKUP_THROTTLE_PERIOD` above 0). |
| `coterm` | At least one Coterm is set up. |

### `views`

Page views since the last report the console accepted, keyed by page.

The panel is a client-side app, so the server never sees a page change. The
browser counts each finished navigation inside the signed-in panel by its route
pattern: the paths of the matched react-router routes joined, such as
`/admin/nodes/:nodeId/templates`. A pattern never carries a value from the URL
(`resources/scripts/lib/telemetry/pageViews.ts`). It doesn't count a change to
the query string alone (such as paging a table), the sign-in screen, or the
"page not found" screen. While telemetry is off, the browser counts nothing and
sends nothing.

Every five minutes, and when the tab is hidden, the browser posts its counts to
`POST /api/client/telemetry/page-views` with `fetch` and `keepalive`, so the
last batch survives the tab closing. The body is nothing but
`{"<route pattern>": <count>}`. A batch that fails is dropped, not retried. The
endpoint takes only signed-in browser sessions and at most ten batches a minute
from one account. A batch may hold up to 50 pages, and it adds at most 20 to
any one page. Nothing about the sender is stored.

Patterns come from the browser, so the server counts only the pages listed in
`app/Services/Telemetry/PageKeys.php` and names each page from that list. A
user can't put free text into the report through this. Routes that only
redirect, like `/admin/servers/:id/settings`, aren't listed, so the page they
lead to is counted once.

| Route pattern | Key |
| --- | --- |
| `/` | `index` |
| `/servers/:id` | `servers.show` |
| `/servers/:id/backups` | `servers.backups` |
| `/servers/:id/settings/general` | `servers.settings.general` |
| `/servers/:id/settings/hardware` | `servers.settings.hardware` |
| `/servers/:id/settings/network` | `servers.settings.network` |
| `/servers/:id/settings/security` | `servers.settings.security` |
| `/servers/:id/terminal` | `servers.terminal` |
| `/admin` | `admin.index` |
| `/admin/locations` | `admin.locations.index` |
| `/admin/nodes` | `admin.nodes.index` |
| `/admin/nodes/:nodeId` | `admin.nodes.show` |
| `/admin/nodes/:nodeId/servers` | `admin.nodes.servers` |
| `/admin/nodes/:nodeId/isos` | `admin.nodes.isos` |
| `/admin/nodes/:nodeId/templates` | `admin.nodes.templates` |
| `/admin/nodes/:nodeId/addresses` | `admin.nodes.addresses` |
| `/admin/nodes/:nodeId/settings/general` | `admin.nodes.settings.general` |
| `/admin/servers` | `admin.servers.index` |
| `/admin/servers/:id` | `admin.servers.show` |
| `/admin/servers/:id/settings/general` | `admin.servers.settings.general` |
| `/admin/servers/:id/settings/hardware` | `admin.servers.settings.hardware` |
| `/admin/ipam` | `admin.ipam.index` |
| `/admin/ipam/:poolId/addresses` | `admin.ipam.addresses` |
| `/admin/users` | `admin.users.index` |
| `/admin/users/:id/settings/general` | `admin.users.settings.general` |
| `/admin/users/:id/servers` | `admin.users.servers` |
| `/admin/coterms` | `admin.coterms.index` |
| `/admin/tokens` | `admin.tokens.index` |
| `/admin/settings` | `admin.settings.index` |

The counters live in the cache. A report reads them without resetting them,
and only once the console has accepted it does the panel take the sent amounts
back off, so a view recorded while a report is in flight is counted the next
day.

## Changing the report

Add a key to the builder (`app/Services/Telemetry/TelemetryReportBuilder.php`)
and document it here. `ReportTest` fails for a resource or feature key missing
from this file, and `PageViewsTest` for a page in `resources/scripts/routers`
missing from `PageKeys`. Change `schema` only for a change an older console
can't read, and change the console first.
