# Announcements feed

The console publishes one signed JSON file. Panels fetch it hourly, verify it, and
show matching announcements to admins. It is public: no license key, no account,
nothing sent about the install. A lapsed or unlicensed panel still runs someone's
Proxmox nodes, so a security notice has to reach it too.

## Transport

- URL: `https://convoypanel.com/api/v1/announcements` (the panel reads it from
  `config('convoy.announcements.url')`, so the host can move).
- Plain `GET`, no headers required. The panel sends `User-Agent: Convoy/<version>`
  and nothing else. The endpoint may accept `?panel=&php=` for people checking it by
  hand; the panel never passes them, since filtering happens locally and the feed
  must not learn anything about the install.
- Served with `Cache-Control: public, max-age=300, stale-if-error=86400` and an
  `ETag`, so the CDN keeps answering while the console is down for a deploy. The
  response is regenerated when an announcement is published, edited or withdrawn,
  not rendered per request.
- Kept small: only announcements that are published and not expired. Old ones drop
  out of the file; they are not deleted from the console.

## Envelope

One file, so the payload and its signature can never be served from different
cache generations.

```json
{
  "key_id": "2026-10",
  "signature": "<base64 Ed25519 signature over the decoded payload JSON>",
  "payload": "<base64 of the JSON document below>"
}
```

- Ed25519 via `sodium_crypto_sign_detached` on the console, verified in the panel with
  `sodium_crypto_sign_verify_detached`. The signature covers the JSON document itself,
  that is `base64_decode(payload)`, not the base64 text: the console signs before it
  encodes. Verify the decoded bytes exactly as received and only then parse them.
  ext-sodium is bundled in the official PHP image, so no new dependency on either side.
- The private key lives only in the console's environment (`ANNOUNCEMENTS_SIGNING_KEY`).
  The panel ships the public keys in `config/convoy.php`, keyed by `key_id`, so a key
  can be rotated by publishing with a new id once panels that know it are out.
- A panel that cannot verify the file keeps what it last verified and logs a warning.
  It never shows unverified content.

## Payload

```json
{
  "generated_at": "2026-10-21T22:00:00Z",
  "announcements": [
    {
      "id": "2026-10-21-php2shell",
      "type": "security",
      "severity": "critical",
      "title": "PHP2Shell: your panel's PHP needs patching",
      "body": "Two critical bugs in PHP let anyone run code on a server that answers web requests with PHP. Run `convoy upgrade`, or follow the steps in the announcement.",
      "url": "https://github.com/ConvoyPanel/panel/discussions/180",
      "affects": {
        "panel": null,
        "php": [
          { "before": "8.2.35" },
          { "from": "8.3.0", "before": "8.3.36" },
          { "from": "8.4.0", "before": "8.4.27" },
          { "from": "8.5.0", "before": "8.5.12" }
        ]
      },
      "published_at": "2026-10-21T16:30:00Z",
      "expires_at": null
    }
  ]
}
```

| Field | Meaning |
|---|---|
| `id` | Stable slug. Dismissals and "already emailed" are keyed on it, so editing an announcement keeps its id. |
| `type` | `security`, `release`, `maintenance` or `info`. Decides the icon and default placement. |
| `severity` | `critical`, `high`, `medium` or `low`. Required for `security`; null otherwise. |
| `title` | One line, plain text. |
| `body` | Short Markdown. Rendered with links and inline code only; no raw HTML, no images. |
| `url` | Where to read more. Must be on `convoypanel.com` or `github.com/ConvoyPanel`; the panel refuses to link anywhere else even if the signature verifies. |
| `affects.panel` | List of version ranges, or null for every version. |
| `affects.php` | List of version ranges against `PHP_VERSION`, or null. |
| `published_at` | When it went live. The panel lists newest first. |
| `expires_at` | After this the console drops it from the feed and the panel hides it. Null means until withdrawn. |

### Version ranges

A range is `{ "from": "x.y.z", "before": "x.y.z" }`; either bound may be omitted.
`from` is inclusive, `before` exclusive. A list matches if any range matches. An
announcement applies to a panel when `affects.panel` is null or matches the panel's
version **and** `affects.php` is null or matches its PHP version.

Comparison is PHP's `version_compare` after stripping a leading `v`. It orders
`4.7.0-rc.3` below `4.7.0`, so a range `{ "before": "4.7.0" }` covers the release
candidates too. A panel reporting `canary` (built from source) never matches a
`panel` range, but still matches `php` ranges, which is what a developer on a
vulnerable PHP needs.

## What the panel does with it

- `announcements:check` runs hourly from the scheduler, with a 10 second timeout, and
  caches the verified payload for 7 days. Requests never fetch; they read the cache.
  `CONVOY_ANNOUNCEMENTS_URL=` (empty) turns the check off for air-gapped installs.
- Only admins see announcements. A matching `critical` one is a banner on every
  admin page and cannot be dismissed; `high` and below show on the dashboard and can
  be dismissed per admin, keyed by `id`.
- A new matching `critical` or `high` announcement emails every admin once, if mail
  is configured. "Already emailed" is recorded per `id`.
- Settings → Updates lists every current announcement, matching or not, with the
  reason it does or doesn't apply ("this panel runs PHP 8.2.35").
- The existing GitHub `releases/latest` check stays and is shown on the same page.
  The feed is for things a version number alone can't say.

## What the console does with it

- `Announcement` rows with the fields above, plus `withdrawn_at` and which channels
  it goes out on (`feed`, `email`).
- Publishing, editing or withdrawing regenerates and re-signs `announcements.json`.
- `announce:send {id} [--to=active|all]` queues one `AnnouncementMail` per license
  holder that renders the row's body; nothing per announcement is written in code.
- Authoring: an admin-only form if the console grows an admin area, otherwise
  `announce:create path/to/announcement.md` with the fields as front matter.
