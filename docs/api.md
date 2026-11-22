# API

Everything the panel does goes through its JSON API, so it can be run without a browser. There are two APIs:

| | Application API | Client API |
| --- | --- | --- |
| Base path | `/api/application` | `/api/client` |
| For | Billing systems, provisioning, infrastructure as code | One user's own servers: a CLI, a customer's scripts |
| Token | Application token, owned by the panel | Personal access token, owned by a user |
| Created in | Admin → Tokens, or `php artisan tokens:create` | Account → API keys |
| Reference | `/api/docs` | `/api/docs/client` |

The application API is the admin area's own API, so anything an administrator can do in the panel, an application
token can do too, including the per-server operations from the client area (rebuilds, backups, firewall, credentials,
consoles). It serves those on the same paths as the client API, under `/api/application/servers/{server}/...`.

## Reference

`/api/docs` and `/api/docs/client` are generated from the code, so they match the running panel. Any signed-in
account can read them. To publish them without signing in, set `API_DOCS_PUBLIC=true`.

The raw OpenAPI documents are at `/api/docs/application.json` and `/api/docs/client.json`. To write them to a file
instead:

```bash
php artisan scramble:export
php artisan scramble:export --api=client
```

The panel builds the reference when it's requested, which takes a few seconds. Run `php artisan scramble:cache`
after an upgrade to build it once ahead of time.

## Authentication

Send the token as a bearer token:

```bash
curl https://panel.example.com/api/application/servers \
  -H "Authorization: Bearer $CONVOY_TOKEN" \
  -H "Accept: application/json"
```

### Your first token

The panel only creates tokens for a signed-in administrator who has confirmed their identity. To get one without the
panel, for example when a provisioning tool sets up a new install, run this on the host:

```bash
php artisan tokens:create --name=terraform --plain
```

`--plain` prints only the token, so it can be captured: `TOKEN=$(php artisan tokens:create --name=ci --plain)`. In the
container install, run it as `convoy artisan tokens:create ...`.

| Option | |
| --- | --- |
| `--name` | Required. What the token is called in the panel. |
| `--ability` | An ability such as `servers:write`. Repeat for more. Leave it out for full access. |
| `--allow` | An IP address or CIDR the token can be used from. Repeat for more. Leave it out to allow any address. |
| `--expires` | A date, or a relative time such as `+90 days`. Leave it out for a token that never expires. |

### Abilities

A token can be limited to some resources, each with `read` or `write`: `servers:read`, `servers:write`, `servers:*`,
or `*` for everything. `write` includes `read`. The resource is the first part of the path after the API's base path,
so `servers:write` covers `/api/application/servers/{server}/backups`.

A personal access token's only resource is `servers`. Any personal access token can call `GET /api/client/user`, to
look up who it belongs to.

### Expiry

Both kinds of token can be given an expiry (`expires_at` when you create one). Once it passes, the token is refused with
401, and it's deleted a day later.

### Restricting a token to your servers

An application token can be limited to the IP addresses it's used from (`allowed_networks`, or `--allow` on the
command line). A request from anywhere else is refused with 403 and the code `token_ip_not_allowed`.

### Identity confirmation

In the panel, rebuilding a server and setting a root password ask the user to confirm who they are first. Tokens skip
this step, because creating one already required it.

### Seeing what a token did

The audit log names the token behind every change made over the API. Application tokens all act as `System`, so the
token's name is what tells one integration's changes from another's. Entries carry it as `token: { id, name }`, and
`GET /api/application/audit-logs?filter[api_token_id]=<id>` lists everything one token did. Revoking a token keeps its
name on the entries but sets `id` to null.

## Rate limits

Each token can make 240 requests a minute, counted per token across both APIs. Responses carry `X-RateLimit-Limit`
and `X-RateLimit-Remaining`, and a request over the limit gets 429 with `Retry-After`.

An application token restricted to an IP allowlist has no limit. Set `API_RATE_LIMIT` to change the limit, or `0` to
turn it off. The panel's own browser session isn't limited.

## Retrying writes safely

Send an `Idempotency-Key` header with any write, using a value that's unique to that operation, such as your order ID
or a UUID. If the response gets lost and you send the same request again with the same key, the panel returns the first
response instead of doing the work twice. Replayed responses have `Idempotent-Replayed: true`.

- Keys are kept for 24 hours (`API_IDEMPOTENCY_TTL_HOURS`) and are separate for each token.
- Only successful responses are kept. If the first request failed validation, fix it and send it again with the same
  key.
- Reusing a key for a different request (another path or body) returns 422 with the code `idempotency_key_reused`.
- If the first request is still running, a retry gets 409 with the code `idempotency_key_in_progress`. Wait for the
  time in `Retry-After`, then try again.

## External IDs

Servers and users take an `external_id`, a string of up to 191 characters that holds the ID your own system uses for
them. It's unique, so a retried create that already succeeded fails validation instead of creating a duplicate. Look
records up by it with `GET /api/application/servers?filter[external_id]=...` or
`GET /api/application/users?filter[external_id]=...`. Customers can't see a server's external ID.

## Creating servers

`POST /api/application/servers` can choose where a server goes for you:

- **`location_id`** instead of `node_id`. The panel picks the location's node with the most free memory that has room
  for the server's CPU and memory, a storage with room for the disk, and a bridge with enough free addresses. If no
  node fits, the error is on `location_id`.
- **`node_id` alone.** If you leave out `storage_id` or `limits.network_interface_id`, the panel picks them on that
  node.
- **`server_preset_uuid`.** Fills in everything a saved preset holds. Anything you also send overrides the preset.

`ssh_keys` takes a list of public keys, which are installed with the OS. When you send keys, `account_password` is
optional. Rebuilds (`POST /servers/{server}/settings/reinstall`) take `ssh_keys` too.

A create returns once the server is saved. The OS installs in the background. To follow it, poll
`GET /api/application/servers/{server}/deployment`, or subscribe a webhook to `server.installation.completed` and
`server.installation.failed`.

## Webhooks

A webhook is a URL the panel POSTs events to. Create webhooks under Admin → Webhooks, or with
`POST /api/application/webhooks`.

### Events

A webhook can subscribe to any of these:

- **Audit events.** Everything the audit log records, under its audit name: `admin.server.created`,
  `server.backup.created`, `auth.login.succeeded` and so on. `GET /api/application/webhooks/events` lists them all.
- **Results of background work.** These don't appear in the audit log because nobody performed them:
  - `server.installation.completed` and `server.installation.failed`, for installs, rebuilds and imports
  - `server.migration.completed` and `server.migration.failed`
  - `server.backup.completed` and `server.backup.failed`

A subscription can also use a pattern ending in `*`: `server.*`, `admin.user.*`, or `*` for everything.

### Payload

```json
{
  "id": "9a1c0e4e-8d0c-4b8e-9d55-0f3c2b1a7e21",
  "event": "server.installation.completed",
  "occurredAt": "2026-11-21T12:00:00Z",
  "data": {
    "server": { "id": 12, "uuid": "…", "externalId": "whmcs-42", "name": "web-1", "lifecycle": "ready", "…": "…" },
    "deployment": { "id": 31, "type": "install", "status": "completed" }
  }
}
```

For an audit event, `data` is the audit entry, shaped as `GET /api/application/audit-logs` returns it, including the
token that made the change. `id` is the delivery's ID. It stays the same when a delivery is retried or sent again, so
use it to ignore duplicates.

### Verifying a delivery

Every delivery is signed with the webhook's secret, which you get once, when you create the webhook or rotate its
secret. The headers are:

| Header | |
| --- | --- |
| `Convoy-Event` | The event name. |
| `Convoy-Delivery` | The delivery ID, the same as `id` in the body. |
| `Convoy-Signature` | `t=<unix time>,v1=<signature>` |

The signature is the hex HMAC-SHA256 of `<t>.<raw request body>`, keyed with the secret. To check a delivery,
compute it over the body exactly as you received it, compare the two with a constant-time comparison, and reject
deliveries whose `t` is more than a few minutes away from your clock.

```php
[$t, $v1] = sscanf($_SERVER['HTTP_CONVOY_SIGNATURE'], 't=%d,v1=%s');
$body = file_get_contents('php://input');

$valid = hash_equals(hash_hmac('sha256', $t.'.'.$body, $secret), $v1)
    && abs(time() - $t) < 300;
```

### Retries

A delivery counts as successful when your endpoint answers with a 2xx within 10 seconds. Otherwise the panel tries
again 10 seconds, 1 minute, 5 minutes, 30 minutes and 2 hours later, then marks the delivery failed. Each delivery
shows how many attempts it took and the last response. To send one again, use
`POST /api/application/webhooks/{webhook}/deliveries/{delivery}/redeliver`. To check that your endpoint works, send a
`webhook.ping` with `POST /api/application/webhooks/{webhook}/test`. Deliveries are kept for 30 days.

## Errors

Errors are JSON with a `message`. Validation errors (422) add `errors`, keyed by field. Errors a client might need to
tell apart also carry a stable `code`, such as `token_ip_not_allowed` or `idempotency_key_reused`.
