# Telemetry

Once a day the panel sends an anonymous usage report to the Convoy console at
`convoypanel.com`. It shows how many installations exist, how big they are,
which versions they run, and which pages get used. It is the only analytics
the panel has. The panel loads no third-party scripts; the Plausible script
that 4.x loaded on every page is gone.

This file is the contract between the panel and the console. The console's
request validation (`StoreTelemetryReportRequest`) points here, and
`tests/Support/TelemetryContract.php` holds the panel to the same rules.

This is the 5.x report. Convoy 6 sends the same schema with keys of its own;
its `docs/telemetry.md` lists them.

## What is never sent

- Names, email addresses, IP addresses, hostnames, FQDNs or URLs, including
  `APP_URL`.
- The license key, `APP_KEY`, or anything derived from them.
- Anything typed into the panel: server names, descriptions, activity log
  properties.
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
- **`php artisan c:telemetry:disable`** does the same from the command line.
  `c:telemetry:enable` turns it back on, and `c:telemetry:show` prints the
  report.
- **`CONVOY_TELEMETRY_ENABLED=false`** stops the reports and the page-view
  counting. The admin switch then shows as off and can't be turned on.
- **`CONVOY_TELEMETRY_URL=`** (empty) does the same, for an install that can't
  reach the internet.

The environment switches don't contact the console, so they don't erase
anything already sent. Use the admin switch for that.

After the upgrade that adds telemetry, every admin sees a notice saying it is
on, with links to the report and to the switch, until they dismiss it.

## When it is sent

The scheduler checks every minute, but each installation reports at its own
minute of the UTC day, taken from its installation id. This spreads the
console's load over 24 hours. At most one report goes out per UTC day.

A report that fails (a timeout, an error status, a refusal) is logged as a
warning and is not retried. The page views it carried stay on the counters, so
the next day's report includes them and nothing is counted twice.

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
    "nodes": [{ "proxmox": "8.4.1", "agent": null, "cpus": 64, "memory_gib": 256, "servers": 80 }],
    "resources": { "backups": 410, "locations": 3 },
    "features": { "coterm": true, "mail": false },
    "views": { "client.servers.show": 300 }
}
```

General rules:

- Every count is a whole number from 0 to 10,000,000.
- Keys in `resources`, `features` and `views` match
  `^[a-z0-9]+(?:[._-][a-z0-9]+)*$`, are at most 120 characters, and a map has
  at most 200 keys. `features` values are booleans; the others are counts.
- These are the console's limits, and one value past them gets the whole
  report refused. The panel caps a count at 10,000,000 rather than send a
  report the console would refuse.
- A version starts with `major.minor` digits. The panel sends only the digits
  (`8.4.12`, never `8.4.12-1ubuntu2`), and the console keeps only the digits.
- The console drops fields it doesn't know. New fields and keys are additive.
  `schema` changes only for a breaking change.

### `schema`, `installation`

`schema` is `1`. `installation` is a random UUID generated by the migration
that adds telemetry, and replaced when an admin turns telemetry off. It is
stored in the panel's `settings` table (group `telemetry`, the same rows
Convoy 6 reads, so the id survives that upgrade) and is not derived from
`APP_KEY`, `APP_URL` or the license.

### `panel`

| Field | Meaning |
| --- | --- |
| `version` | `config('app.version')`: a release (`5.0.2`, `5.1.0-rc.2`) or `canary` for a source build. |
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

Activity is read from `users.last_active_at`, which the `UpdateLastActiveAt`
middleware updates at most once every five minutes per account, with a query
that leaves `updated_at` alone. Requests made with an API token don't count: a
script calling the API is not someone using the panel. Distinct counts don't
add up across days, so each window is computed and sent on its own.

### `nodes`

One entry per node, at most 500: a panel with more nodes reports its first
500 by id. Each field has its own cap: `cpus` 10,000, `memory_gib` 1,000,000
and `servers` 100,000.

| Field | Meaning |
| --- | --- |
| `proxmox` | The Proxmox VE version the node reports (`/nodes/{node}/status`), or `null` if it doesn't answer. |
| `agent` | Always `null`: 5.x has no node agent. |
| `cpus` | The CPU count the node reports, or `0` if it doesn't answer. |
| `memory_gib` | The node's memory as set in the panel, in GiB, rounded. |
| `servers` | Servers on the node. The total server count is the sum of these, so it isn't repeated in `resources`. |

The panel stores neither a node's Proxmox version nor its CPU count, so the
report asks each node, with a 5 second timeout. It stops asking after 60
seconds in total, so unreachable nodes can't hold the job past its limit; the
rest are sent with `null` and `0`.

### `resources`

Counts of things that exist right now.

| Key | Meaning |
| --- | --- |
| `address_pools` | IP address pools. |
| `addresses.ipv4` | IPv4 addresses in the pools. |
| `addresses.ipv6` | IPv6 addresses in the pools. |
| `api_tokens.account` | API tokens accounts created for themselves. |
| `api_tokens.application` | Application API tokens. |
| `backups` | Server backups. |
| `coterms` | Coterm instances. |
| `isos` | ISOs across all nodes. |
| `locations` | Locations. |
| `nodes.without_tls_verification` | Nodes whose TLS certificate the panel doesn't verify. |
| `servers.install_failed` | Servers whose installation failed. |
| `servers.suspended` | Suspended servers. |
| `ssh_keys` | SSH keys saved to accounts. |
| `template_groups` | Template groups across all nodes. |
| `templates` | Templates across all nodes. |

### `features`

Switches that describe how the installation is set up.

| Key | `true` when |
| --- | --- |
| `coterm` | At least one node uses Coterm for its console. |
| `mail` | The panel can send mail: a mailer other than `log` or `array`, and for SMTP a host other than the example `mailhog`. |

### `activity`

Not sent. 5.x's activity log has no event names that are written consistently
enough to count.

### `views`

Page views since the last report the console accepted.

The panel is a client-side app, so the server never sees a page change. Once
each navigation settles, the browser posts the route pattern of the page it
landed on (`/servers/:id/backups`, never the address itself) to
`POST /api/client/telemetry/views`, and the panel adds one to that page's
counter. The endpoint takes only signed-in browser sessions. While telemetry is
off, the page isn't told to report and the endpoint counts nothing.

Only the patterns below are counted, each under a fixed key, so nothing a
browser makes up reaches the report. Layouts and redirects are left out, and
the page they lead to is counted instead: a settings index such as
`/servers/:id/settings` goes on to its general tab.

| Route pattern | Key |
| --- | --- |
| `/` | `client.servers.index` |
| `/servers/:id` | `client.servers.show` |
| `/servers/:id/backups` | `client.servers.backups` |
| `/servers/:id/settings/general` | `client.servers.settings.general` |
| `/servers/:id/settings/hardware` | `client.servers.settings.hardware` |
| `/servers/:id/settings/network` | `client.servers.settings.network` |
| `/servers/:id/settings/security` | `client.servers.settings.security` |
| `/servers/:id/terminal` | `client.servers.terminal` |
| `/admin` | `admin.overview` |
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
| `/admin/ipam` | `admin.address-pools.index` |
| `/admin/ipam/:poolId/addresses` | `admin.address-pools.addresses` |
| `/admin/users` | `admin.users.index` |
| `/admin/users/:id/settings/general` | `admin.users.settings.general` |
| `/admin/users/:id/servers` | `admin.users.servers` |
| `/admin/coterms` | `admin.coterms.index` |
| `/admin/tokens` | `admin.tokens.index` |
| `/admin/settings/updates` | `admin.settings.updates` |
| `/admin/settings/telemetry` | `admin.settings.telemetry` |
| `/admin/settings/upgrade` | `admin.settings.upgrade` |

The counters live in the cache. A report reads them without resetting them,
and only once the console has accepted it does the panel take the sent amounts
back off, so a view recorded while a report is in flight is counted the next
day.

## Changing the report

Add a key to `app/Services/Telemetry/TelemetryReportService.php` (or a page to
`PageViewCounter::PAGES`) and document it here. `ReportTest` fails for a
resource, feature or page key missing from this file. Change `schema` only for
a change an older console can't read, and change the console first.
