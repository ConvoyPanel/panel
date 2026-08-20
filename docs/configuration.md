# Configuration

Convoy is configured through environment variables. Everything has a default in
`config/` except the handful of values that are specific to your install, so a
working `.env` is short.

- **`.env.example`** — the local development template. Copy it, fill in the
  blanks, done.
- **`.env.docker.example`** — the production template used by the installer.
- **`.env.reference`** — every variable Convoy reads, with its default, in the
  order they appear below. Reference material; not something to copy.

A variable you do not set takes the default shown here. Setting a variable to
its default has no effect, so prefer leaving it out — a short `.env` is easier to
review than one where the meaningful lines are buried.

## Required

These have no useful default and must be set.

| Variable | Notes |
| --- | --- |
| `APP_KEY` | 32 random bytes, base64 encoded. Generate with `php artisan key:generate`. Every process — web, queue worker, scheduler — must share the same value: it decrypts sessions and encrypted columns, so changing it invalidates both. |
| `APP_URL` | The URL customers reach the panel on, including the scheme. Password-reset links, SSO deep links and asset URLs are all built from it. |
| `DB_*` | Connection details for Postgres. |
| `REDIS_*` | Connection details for Redis. Not optional — see below. |

### Redis is required

Horizon, the cache and the session store all run on Redis, so there is no
configuration in which Convoy runs without it. Horizon in particular has no
database-backed mode: every Proxmox action is a queued job, and the queue is
Redis. Plan for it as part of the install rather than as an add-on.

## Application

| Variable | Default | Notes |
| --- | --- | --- |
| `APP_NAME` | `Convoy` | Shown in the UI and used as the default mail sender name. |
| `APP_ENV` | `production` | Anything other than `local` disables developer conveniences. |
| `APP_DEBUG` | `false` | Never `true` on an internet-facing install: the debug page renders configuration and stack traces to whoever triggered the error. |
| `APP_TIMEZONE` | `UTC` | |
| `APP_LOCALE` | `en` | |
| `TRUSTED_PROXIES` | unset | Comma-separated IPs/CIDRs of proxies whose forwarded client-IP headers Convoy may trust. |

### Trusted proxies

Leave `TRUSTED_PROXIES` unset when the panel faces the internet directly. When a
load balancer or CDN sits in front of it, set it to that proxy's address —
otherwise the audit log and the rate limiter both see the proxy as the client,
which means one address for every customer.

Never use `*` on a public origin: it tells Convoy to believe whatever
`X-Forwarded-For` a request arrives with, which lets anyone forge their apparent
address and evade the rate limiter.

### Maintenance mode

| Variable | Default | Notes |
| --- | --- | --- |
| `APP_MAINTENANCE_DRIVER` | `file` | `file` or `cache`. |
| `APP_MAINTENANCE_STORE` | `redis` | Which cache store holds the flag when the driver is `cache`. |

`file` records maintenance mode on local disk, so `artisan down` only marks down
the process that ran it. That is correct for a single-process install and wrong
for a containerised one, where the web, worker and scheduler are separate
processes — those should set `APP_MAINTENANCE_DRIVER=cache` so all three go down
together.

Note that Convoy overrides Laravel's default for `APP_MAINTENANCE_STORE`.
Laravel points it at the `database` cache store, which reads a `cache` table
Convoy has no migration for; leaving it at the framework default and selecting
the `cache` driver makes every request fail with `relation "cache" does not
exist`.

## Sessions

| Variable | Default | Notes |
| --- | --- | --- |
| `SESSION_DRIVER` | `redis` | |
| `SESSION_LIFETIME` | `525600` | Minutes — one year. Laravel's own default of 120 signs operators out of the panel far more aggressively than the way it is actually used warrants. |

## Logging

| Variable | Default | Notes |
| --- | --- | --- |
| `LOG_CHANNEL` | `stack` | `stack` writes files under `storage/logs`, which is what a bare-metal install wants. Containers should use `stderr` so output lands in `docker logs`. |
| `LOG_LEVEL` | `debug` | `info` is a better production default; `debug` is noisy enough to matter on a busy panel. |

## Mail

Standard Laravel mailer settings: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`,
`MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`,
`MAIL_FROM_NAME`. When `MAIL_MAILER=mailgun`, set `MAILGUN_DOMAIN` and
`MAILGUN_SECRET` (and `MAILGUN_ENDPOINT`, default `api.mailgun.net`, for the EU
region).

## Queue dashboard

| Variable | Default | Notes |
| --- | --- | --- |
| `HORIZON_DOMAIN` | unset | Serve Horizon from a dedicated subdomain. |
| `HORIZON_PATH` | `horizon` | Serve it from a different path. |

## Settings cache

| Variable | Default | Notes |
| --- | --- | --- |
| `SETTINGS_CACHE_ENABLED` | `true` | Caches resolved settings so reads do not hit the database. Invalidated automatically on save; leave enabled in production. |
| `SETTINGS_CACHE_MEMO` | `false` | Additionally memoize within a single request. Off by default because it makes settings written mid-request invisible to the rest of that request. |

## Retention and pruning

Consumed by the scheduled prune commands in `routes/console.php`. These only run
if the scheduler is running.

| Variable | Default | Notes |
| --- | --- | --- |
| `APP_AUDIT_PRUNE_DAYS` | `90` | Days of audit log to keep. Security events (authentication, credential and token changes) are exempt and kept forever. |
| `BACKUP_PRUNE_AGE` | `360` | Days before a backup is eligible for pruning. |
| `DEPLOYMENT_RETENTION_PERIOD` | `90` | Days of deployment records to keep. |
| `DEPLOYMENT_STUCK_AGE` | `1440` | Minutes after which an in-progress deployment is treated as stuck. |

## Rate limits

| Variable | Default | Notes |
| --- | --- | --- |
| `BACKUP_THROTTLE_LIMIT` | `2` | Backups allowed per server... |
| `BACKUP_THROTTLE_PERIOD` | `600` | ...per this many seconds. |

## Outbound HTTP

Applied to calls out to Proxmox and Anchor.

| Variable | Default | Notes |
| --- | --- | --- |
| `GUZZLE_CONNECT_TIMEOUT` | `5` | Seconds to establish a connection. |
| `GUZZLE_TIMEOUT` | `15` | Seconds for the whole request. Raise it if a hypervisor is slow to answer, but not far: a long timeout means a wedged node holds queue workers open instead of failing fast. |

## Update checks

| Variable | Default | Notes |
| --- | --- | --- |
| `UPDATE_CHECK_REPOSITORY` | `ConvoyPanel/panel` | The repository the admin dashboard checks for newer releases. |

## Metrics (optional)

| Variable | Default | Notes |
| --- | --- | --- |
| `VICTORIAMETRICS_URL` | unset | Endpoint backing the admin dashboard's metric history (deltas and sparklines). Leave unset to disable; the dashboard works without it. |

## SSO deep links (optional)

Minted via `POST /api/application/users/{user}/generate-sso-token`.

| Variable | Default | Notes |
| --- | --- | --- |
| `SSO_LINK_TTL` | `60` | Signed-link lifetime, in seconds. |
| `SSO_AUDIT_CHANNEL` | `LOG_CHANNEL` | Log channel each consumed link is written to. |

## OAuth / OIDC (optional)

Convoy acts as the Relying Party — see `config/oauth.php`. A provider appears on
the login screen only when its `*_ENABLED` flag is true **and** its client id and
secret are both set.

| Variable | Default | Notes |
| --- | --- | --- |
| `OAUTH_REGISTRATION` | `false` | Auto-create a non-admin user for an identity matching no existing account. |
| `OAUTH_LINK_BY_VERIFIED_EMAIL` | `true` | Link a provider identity to an existing account by verified email. |

Per provider — `GOOGLE`, `GITHUB`, `GITLAB`:

- `OAUTH_<PROVIDER>_ENABLED` (default `false`)
- `OAUTH_<PROVIDER>_CLIENT_ID`
- `OAUTH_<PROVIDER>_CLIENT_SECRET`
- `OAUTH_<PROVIDER>_REDIRECT_URI` (default `/api/auth/oauth/<provider>/callback`)

Generic OpenID Connect works against any standards-compliant IdP (Keycloak,
Authentik, Okta). Set `OAUTH_OIDC_BASE_URL` to the issuer and the endpoints are
read from its `/.well-known/openid-configuration`; `OAUTH_OIDC_AUTH_URL`,
`OAUTH_OIDC_TOKEN_URL` and `OAUTH_OIDC_USERINFO_URL` only need setting when
discovery is non-standard. `OAUTH_OIDC_LABEL` (default `OpenID Connect`) is the
login-button text and `OAUTH_OIDC_SCOPES` (default `profile,email`) is a comma
list; `openid` is always requested.
