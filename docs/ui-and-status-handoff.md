# Handoff — admin UI pass + node status

Written 2026-07-17. Everything below is either landed on `next` or listed in
§3 as not started. Nothing here is "in progress" — the tree is clean.

`next` **has since been rebased and pushed** — as of 2026-07-17 it is one commit
ahead of `origin/next`. Every SHA this file originally cited was rewritten by
that rebase; the hashes below are the current ones. If a hash here does not
resolve, assume another rewrite and search by subject line rather than trusting
the table.

## 1. Landed

| SHA | What |
| --- | --- |
| `f8caf887` | dropped the auth handoff's stale outstanding list |
| `edfa75a5` | boolean form fields no longer latch themselves uncontrolled |
| `852a50bc` | data table: floating selection bar; select column stops bloating |
| `3c4a3bad` | node overview names *why* a node is unreachable |
| `da028fa3` | node reachability polled by the scheduler (slice 1) |
| `f587f044` | node settings stops pairing a two-field card with a six-field one |
| `11f9358c` | server overview stat row reads as one design (§6) |
| `bfe9f66f` | overview rows stop going 4-up before they fit (§6) |
| `9513356b` | live tiles stop claiming to load forever (§7) |
| `54647dee` | poll records guest power state (slice 2, write half) |
| `8da5fd18` | client server list shows power state (slice 2, read half) |

The design options these were picked from are published at
<https://claude.ai/code/artifact/bcc1335b-c91a-4e2a-afcc-4e4cd749602f> (the
floating pill and "state on the node, cause in the card" were the chosen ones;
the toolbar-swap and toast alternatives are written up there with their
trade-offs if either is ever revisited).

## 2. Findings worth keeping

- **Base UI latches controlled-ness on the first render only**
  (`useControlled.mjs`: `useRef(controlled !== undefined)`). A form built as
  `useForm({ resolver })` with no `defaultValues` hands `undefined` to its
  checkbox on render one, which pins it uncontrolled *for life* — every later
  `form.reset(record)` is ignored. This is why node settings showed TLS
  verification unchecked while the database said `true`, and why clicking once
  to turn it off submitted it **on** (the click toggles Base UI's internal
  `false → true` and reports `true`). Silent in production twice over: the
  warning is `NODE_ENV`-gated, and text inputs in the same form populate fine.
  Fixed in the primitives (`?? false`); full write-up in `docs/card-design.md`.
- **`width` on a table cell is a suggestion, not a cap.** Under
  `table-layout: auto` the browser tops each column up with a share of the
  table's slack *proportional to the declared widths* — and react-table merges
  `size: 150` into every column def, so the 32px select column drew a 150-sized
  share and rendered at 92px. Measured, not theorised: `[92, 432, 432, 138]`
  before, `[32, 427, 587, 48]` after.
- **`isError` can be permanently invisible under a short `refetchInterval`.**
  Every fetch restarts the retry cycle, so `isError` is only true between one
  cycle failing and the next starting. At `refetchInterval: 50` that window never
  paints — which is why the four live tiles sat on their skeleton forever rather
  than erroring. Raising the interval only trades it for a flicker
  (skeleton → Unknown → skeleton). Latch the failure instead; `useServerState`
  does, and exposes `isUnknown`. Don't key a UI state off `isError` on a polling
  query.
- **`ArrayStore` expires against `microtime()`, so `$this->travel()` cannot age a
  cache entry out.** Carbon's test clock does not reach it. Assert on the expiry
  handed to `Cache::put` (with `freezeTime()`, or the two `now()`s differ by
  microseconds). Model TTLs like `status_checked_at` *are* travellable, since they
  read through Carbon — so the two halves of one test can disagree.
- **The array cache is not torn down between tests, but the database is.** Node
  ids restart, so a previous test's `node:1:vm-states` is sitting under the new
  node's key and a test that never polled reads the last test's guests.
  `Cache::flush()` in `beforeEach`.
- **`Http::fake()` merges stubs, it does not replace them.** Re-faking the same
  URL pattern mid-test silently keeps the first stub. Flip a closure flag
  instead — see `NodeStatusPollServiceTest`.
- **`CardContent` is `p-4 pt-0`**: it assumes a `CardHeader` above it supplies
  the top padding. A `<Card><CardContent>` with no header puts its text on the
  border. This was the old status banner's formatting bug and will bite anything
  else that skips the header.
- `php` is not on the host, only in ddev — so `npm run build` fails at its
  `prebuild` (`types:generate`). Use `npx vite build` when no PHP data class
  changed, or run the generator through ddev.

## 3. Outstanding

The node settings layout is **done** — see §5. The client server overview row is
**done** — see §6.

- **Test connection on the settings page.** `POST /api/admin/nodes/test-connection`
  and `TestConnectionButton` already exist and are used by the create flow;
  settings rolls its own connection card without one. The catch: the endpoint
  builds a `new Node` from the request, but settings leaves the token fields
  blank to mean "keep the existing token" — so a saved node needs a node-scoped
  variant that falls back to the stored secret.
- **Node status slices 3–4** — alerting and #104's dashboard resource overview.
  Slice 3 is **parked**: the maintainer does not want email alerts yet. Slice 2
  is done for the *client* list only — the **admin server list still shows no
  power state**, and wiring it is just `PowerStateBadge` plus whatever data the
  admin list returns. See `docs/node-status-plan.md`.
- **The four live tiles poll as fast as PVE answers.** `serverQueries.state`
  carries `refetchInterval: 50`, which is not "20 requests a second" — React
  Query waits for each response before scheduling the next, so it means "re-poll
  50ms after the last one lands" (measured: 6 requests per 10s against an
  unreachable node; bounded only by latency against a healthy one). It predates
  the Wayfinder refactor and appears deliberate — it is what makes the CPU and
  memory graphs live. Left alone. Slice 4's "read path never touches PVE" is what
  would properly retire it, at the cost of the tiles stepping once a minute.
- **The selection pill centres on the viewport, not the content area**, so it
  sits ~127px left of the table's centre with the sidebar open. Deliberate:
  `fixed` is the only positioning that neither shifts layout nor scrolls away,
  there is no sidebar-width CSS variable to offset against, and a zero-height
  `sticky` fights the parent's `space-y-4` margins. Cosmetic; revisit if it
  grates.
- **`ConnectionErrorCode` lives in `App\Enums\Node\Testing`** but is now the
  shared vocabulary for the status endpoint, the poller, and the table — the
  `Testing` namespace is a misnomer. A mechanical move, left alone to keep this
  session's diffs reviewable.

## 4. Environment

- **Node 12 (`Dev Proxmox`) is a real Proxmox host** seeded from `.env` via
  `php artisan db:seed --class=DevNodeSeeder` (retry the documented SIGSEGV).
  It is the only node in the dev database that actually answers; the seeded
  `*.nodes.example.test` fleet now all read **Unreachable / dns_error** in the
  Nodes table, which is correct but makes the list look alarming.
- Browser checks: Playwright + chromium are already installed under a previous
  session's scratchpad. **Log in at `/auth/login`** (not `/login`, which 404s)
  as `test@test.com` / `Zzz!98765`.
- **Every seeded server belongs to `visual-admin@example.test` (id 38), not to
  `test@test.com`** — so `/` ("My Servers", filtered by owner) is *empty* as the
  test user, while `/servers/{uuid}` still opens because that account is an admin.
  Do not try to log in as `visual-admin`: it has a **passkey** second factor, so
  the password is not enough and swapping it does not help. To see the client list
  with data, reassign a few servers (`UPDATE servers SET user_id=1 WHERE id IN
  (...)`) and set them back to 38 afterwards.
- To exercise guest power states without a live guest, write the cache directly —
  `app(GuestStateCache::class)->put($node, [$vmid => 'running'])`. The dev node
  (12) is real but hosts **no qemu guests**, so a real poll leaves the map empty.
- To make node 12 fail on demand (useful for the unreachable states):
  ```sh
  ddev exec psql -U db -d db -c "UPDATE nodes SET verify_tls=true WHERE id=12;"
  ```
  It presents an untrusted certificate, so this yields a real `tls_error`.
  Set it back to `false` to restore.
- `php artisan tinker <script>` hangs; bootstrap Laravel in a plain script and
  run it with `ddev exec php` instead.
- **`ddev start` fails in a Docker Sandbox**: `/etc/hosts` is read-only even
  under sudo, and `ddev-hostname` treats that as fatal. `/usr/local/bin` is not
  writable either, so shadow it on `PATH` instead — a `~/bin/ddev-hostname` that
  is just `#!/bin/sh` + `exit 0`, then `PATH="$HOME/bin:$PATH" ddev start`. The
  entry is not actually needed: nothing in the sandbox resolves `*.ddev.site` by
  name. Reach it with `curl --resolve convoy.ddev.site:443:127.0.0.1` and launch
  chromium with `--host-resolver-rules=MAP convoy.ddev.site 127.0.0.1`. Still run
  the kit's sanity check (`remote_ip` must be `127.0.0.1`) — the point of the
  NO_PROXY entry is that you never drive the *host's* app by accident.
- Playwright's browsers are cached in `~/.cache/ms-playwright`, but the
  `playwright` **module** lived in a previous session's scratchpad and is gone;
  `npm i playwright` into the current one. It resolves the cached browsers, so
  nothing re-downloads.
- The `CheckboxForm` boolean renders a Base UI **button**, not an `input`, so
  `input[name="verifyTls"]` never matches. Use `getByRole('checkbox')`.

## 5. Node settings layout — settled

The contradiction the last handoff flagged was a false one: both statements were
partly right. The **card grid pattern is fine** and stays the documented default;
what was ugly was the *content split* — a two-field General card sharing a grid
row with a six-field Connection card, where the row stretches both to the taller
one and pads General with dead space.

So the pattern kept its place and the page changed: the four cards now stack
full-width, each using its own internal responsive grid for horizontal density
(General puts Display Name beside Location; Connection runs FQDN / Port / Node
Name, then Token ID / Token Secret). `card-design.md`'s "reference layout"
citation moved to `admin/servers.$serverId/settings.lazy.tsx`, which demonstrates
the same grid with *balanced* cards, and now states the rule the old citation
left implicit: pair cards side by side only when they hold comparable amounts of
field.

Verified in the browser at 1440px and 820px, and with a real save round-trip —
display name, CPU count and a `verifyTls` off→on click all persisted correctly
(node 12 was restored to its seeded values afterwards).

## 6. Client server overview row — settled

All three of the maintainer's complaints about the Bandwidth / Storage / System
Specifications row had a single cause each, and all three are fixed. The rules
they generalise into are now in `docs/card-design.md` ("Statistic cards: a meter
is not a footer"); the short version:

- **Mismatched heights** — Specifications was a plain `Card` (`text-base` header,
  no footer) between two `StatisticCard`s (`text-xs` compact header). It now uses
  `StatisticCard` like its neighbours, with its three specs in an internal
  `grid-cols-1 @lg:grid-cols-3`.
- **Stranded progress bars** — `StatisticCard`'s bar lived in a `CardFooter`
  (`border-t bg-muted/50`) with `grow justify-end`, pinning it to the bottom of a
  stretched card. The `footer` prop is now `meter`, rendered inside `CardContent`
  under the value.
- **"Usage unavailable"** — that string sat in the value slot while a `0%` bar
  rendered below it, reading as an empty disk. Storage now shows the disk limit as
  a real number with the subline `Disk limit • guest agent offline` and no bar; the
  warning-triangle tooltip is unchanged. A loading skeleton was also added — the
  card previously showed the unavailable state while the request was still in
  flight, which on an unreachable node is ~20s of lying.

Verified in the browser at 1440px and 820px. Every dev server sits on an
unreachable seeded node, so the *unavailable* branch is what the database gives
you for free; the *available* branch was rendered by stubbing the resources
endpoint with a Playwright `page.route` (`used_bytes`/`total_bytes` are
snake_case — `ResourceController` returns a raw array, not a laravel-data DTO).
It read `13.4 GiB / used of 20 GiB • 67%` with the bar filled to 67% and level
with Bandwidth's.

### The rows went 4-up before they fit

A follow-up (`bfe9f66f`) that turned out to be most of the remaining "mismatched
heights": both rows broke to four columns as soon as the content area cleared
~450–510px, so at laptop width each tile was ~130px and its title wrapped to two
lines — and a wrapped title pushes that card's value down past its neighbours'.
The two rows also disagreed with each other (`@md` vs `@lg`) about the same
decision. Both are now `@5xl` and break together.

**These container queries resolve against `AppLayout`'s `@container` — the whole
content area (`AppLayout.tsx:42`), not the card.** Every `@md`/`@lg` in these
files is therefore a statement about the *page*, which is why thresholds here
cannot be reasoned about from the card's own appearance. Corollary worth knowing
before touching this row again: **Specifications' width is not monotonic in the
page width.** It spans the full row in the 2-col band and half of it in the
4-col band, so it gets *narrower* as the page gets wider — 596px at a 644px
container, 330px at a 724px one. Any threshold picked by arithmetic will be
wrong; measure it (`page.evaluate` comparing each element's height against its
`line-height` catches wrapping directly).

Measured, at 12 widths from 480px to 1600px — no title wraps, no value wraps, no
horizontal overflow, and 1440px unchanged:

| container | tiles | specs columns |
| --- | --- | --- |
| 724 | 169px — "Memory Usage" wraps | 83px — "39.04 GiB" wraps |
| 1024 | 244px — clears | 133px — clears |

`IpamCard`'s `col-span` has to move with the grid: a `col-span-4` left behind in
a 2-col grid adds implicit columns and overflows the row.

While here: the Do/don't list still cited `nodes.$nodeId/settings.lazy.tsx` as the
reference layout, which §5 had already moved to `servers.$serverId`. Stale citation
removed — it now points at the one place that names the file.

## 7. Live tiles, and slice 2

### The tiles no longer claim to load forever

Server State / CPU / Memory / Uptime sat on their skeleton indefinitely whenever a
server's node was unreachable — and since every seeded server sits on an
unreachable node, that was the default view of the page.

The obvious diagnosis (no error branch) is wrong, and adding one changes nothing:
`isError` is never observably true, for the `refetchInterval` reason in §2. The
failure is latched in `useServerState`, which exposes **`isUnknown`** — use that,
not `isError`. It clears on the first success, so a node that recovers repaints
itself.

The cadence was deliberately not touched. `refetchInterval: 50` is what makes the
graphs live; retiring it is slice 4's business, not a bug fix's.

### Slice 2 — guest power state

Done for the **client** list; the **admin** list still shows none.

The plan said slice 2 reused slice 1's poll "with no new call". It did not: slice 1
shipped against `/nodes/{node}/status`, which returns no guests. So the poll now
calls **`/cluster/resources`** — the endpoint the plan's own "one call answers
everything" section always assumed — and reachability means "that call answered".
The alternative (keep `getStatus()`, add a second call) was rejected because a
second endpoint is a second timeout on exactly the nodes that are down.

Two documented deviations from the plan, both in `docs/node-status-plan.md`: the
cache TTL follows `Node::STATUS_TTL_MINUTES` so it lapses with the node status it
came from, and a failed poll leaves the map standing rather than clearing it.

`ServerData::$powerState` is **not** `ServerData::$status`. The latter is Convoy's
lifecycle (built, suspended, installing); the former is whether the guest is
switched on. `ready` no longer draws a badge at all — it is the resting state of
every healthy server, so badging it trained the eye to ignore the one place a
badge means something.
