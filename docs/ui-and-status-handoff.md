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

Two of these are UI complaints with screenshots the maintainer raised directly;
neither has been touched.

- **The node settings page layout (`nodes.$nodeId/settings.lazy.tsx`).** The
  maintainer's words: "needs to be redesigned with the nova cards in mind, the
  layout looks ugly". Currently General / Connection side by side with
  Specifications and Bandwidth full-width beneath; General is mostly empty
  space next to a much taller Connection card.
  **Note the contradiction before starting:** `docs/card-design.md` §"In this
  codebase" cites *this exact file* as the **reference layout** for the card
  grid. One of the two is wrong — either the page is fine and the complaint is
  about something narrower (the ragged card heights?), or the reference needs
  to move. Settle that first, and update `card-design.md` either way.
- **The client server overview cards (`/servers/{id}`).** "These cards look like
  shit" — specifically the Bandwidth Allowance / Storage Usage / System
  Specifications row: mismatched heights, progress bars stranded at the card
  bottom, and "Usage unavailable" sitting where a number should be.
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
