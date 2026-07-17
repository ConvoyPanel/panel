# Handoff — admin UI pass + node status

Written 2026-07-17. Everything below is either landed on `next` or listed in
§3 as not started. Nothing here is "in progress" — the tree is clean.

`next` is **17 commits ahead of `origin/next`** and has never been pushed.

## 1. Landed this session

| SHA | What |
| --- | --- |
| `5e9a34f5` | dropped the auth handoff's stale outstanding list |
| `9d507610` | boolean form fields no longer latch themselves uncontrolled |
| `0077faea` | data table: floating selection bar; select column stops bloating |
| `ffd81c50` | node overview names *why* a node is unreachable |
| `eef42e24` | node reachability polled by the scheduler (slice 1) |

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
- **Node status slices 2–4** — guest power state for the server lists, alerting,
  and #104's dashboard resource overview. Designed in
  `docs/node-status-plan.md`; slice 2 reuses slice 1's poll for free.
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
  `grid-cols-1 @xl:grid-cols-3` (3-up needs `@xl`; at `@lg` the page container is
  still only ~540px and "39.04 GiB" wrapped mid-value).
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

While here: the Do/don't list still cited `nodes.$nodeId/settings.lazy.tsx` as the
reference layout, which §5 had already moved to `servers.$serverId`. Stale citation
removed — it now points at the one place that names the file.
