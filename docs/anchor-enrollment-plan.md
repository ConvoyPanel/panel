# Anchor-first node enrollment — plan

Turn "add a node" from a fourteen-field form into one command run on the host,
the way Tailscale adds a machine. Written 2026-08-20.

The framing that drives the whole design: **an anchor is a machine, a node is a
role that machine takes on.** Every node has an anchor; not every anchor is a
node (a relay is an anchor that never becomes one). Today the schema says the
opposite — `nodes.anchor_id` makes the anchor an accessory bolted onto a node
that already exists.

**The destination is a required anchor** — `nodes.anchor_id` NOT NULL, the
manual create path gone, and the branching that supports both worlds deleted.
This plan gets there in stages rather than in one migration, because the
constraint is cheap once the population is clean and dangerous while it is not.
Slices 1–5 are the enrollment feature; ["Getting to required"](#getting-to-required)
is the ladder to the constraint, and it is where the v4 upgrade question is
answered.

## The problem

Adding a node today is a manual transcription exercise, and every field is
something the host already knows:

| Field | Where the operator gets it | Who actually knows it |
| --- | --- | --- |
| `fqdn`, `port` | typed | the host |
| `name` (PVE node name) | typed, must match exactly | the host |
| `token_id` / `token_secret` | created in the PVE UI, pasted | the host |
| `rootPrivileges`, `privilegeSeparationDisabled` | **two checkboxes the operator ticks to attest** they did it right | the host |
| `socket_count`, `core_count`, `cpu_count`, `memory` | typed, never re-checked | the host |
| `location_id`, `display_name`, `memory_overallocate` | genuine operator policy | the operator |

Only the last row is a real decision. Everything above it is dictation, and two
of those fields are attestations — `z.literal(true)` checkboxes
(`features/nodes/api.ts:48`) that promise the pasted token is `root@pam` with
privilege separation off. Nothing verifies that; a wrong tick surfaces later as
a confusing permission error.

The hardware columns are worse than tedious: nothing ever writes `socket_count`
after creation (`grep socket_count app/` finds only the model and the DTO), so
a RAM upgrade silently leaves the panel scheduling against stale capacity.

Meanwhile the anchor enrollment flow already proves the pattern works — it just
runs in the wrong direction.

## What exists (and why it's the wrong direction)

`AnchorEnrollmentService` + `EnrollmentController` implement a **pre-registration
claim**: the operator creates the `Anchor` row first (name, mode, `public_url`),
the panel mints a token *bound to that row*, and the agent exchanges it for
config. The token identifies a record that already exists.

Tailscale's auth key is the inverse: the key is bound to *nothing*, and the
machine that presents it **creates its own record** out of what it reports about
itself. That inversion is the entire feature. Everything else here follows from
it.

Worth keeping from what's built: the config-writing agent side
(`anchor enroll`, atomic `0600` write), secret rotation on every enroll (the
remediation story is already correct), the `uuid.secret` bearer scheme, and the
heartbeat loop. None of that changes.

## Design

### 1. Enrollment keys replace per-anchor tokens

New table `anchor_enrollment_keys`:

```
id, uuid, name, token_hash (sha256, unique), created_by
mode                 -- 'agent' | 'relay' | null (either)
max_uses, uses       -- null max = reusable without limit
expires_at, revoked_at
auto_approve         -- bool, default FALSE
default_location_id  -- nullable FK; the one policy field a key can pre-answer
default_relay_id     -- nullable FK
timestamps
```

The existing `anchors.enrollment_token_hash` / `enrollment_expires_at` columns
**stay**. They are still the right mechanism for the other job: rotating one
existing installation's secret. Two different questions — "let a new machine
in" versus "re-key this machine" — deserve two different credentials, and
conflating them is what makes the current flow un-invertible.

Defaults: single use, 15 minutes (matching today), `auto_approve = false`. A key
that creates nodes is a far stronger credential than today's token, which could
only claim one pre-made row. It should behave like one.

### 2. The agent reports facts; the panel decides policy

`anchor enroll` keeps its exact CLI shape. The request body gains a
self-report, and the **panel** decides from the token's shape whether it is a
targeted rotation or a key-based enrollment. Old tokens keep working; no CLI
churn, no flag to explain.

```jsonc
{
  "token": "…",
  "mode": "agent",
  "report": {
    "hostname": "pve1.example.com",
    "pve_node_name": "pve1",          // must match nodes.name exactly
    "pve_version": "9.2.2",
    "cluster_name": "prod",
    "cluster_ca_fingerprint": "…",     // the identity ClusterIdentityService trusts
    "cpu": { "sockets": 2, "cores": 32, "threads": 64 },
    "memory_bytes": 549755813888,
    "addresses": ["10.0.0.11", "2001:db8::11"],
    "version": "0.1.0",
    "protocol": { "min": 1, "max": 1 },
    "capabilities": ["console.qemu.vnc", "console.qemu.terminal", "templates.install"]
  }
}
```

Hard rule: **a self-report may never grant privilege.** It fills in facts about
hardware and identity. `location_id`, `memory_overallocate`, and the overage
penalty stay operator policy, supplied by the key's defaults or at approval.
A host that lies about its RAM under-schedules itself; a host that could pick
its own location picks its blast radius.

### 3. The agent mints its own PVE API token

This is the field that makes the difference between "shorter form" and "no
form", and it is available only because the agent already runs as root on the
host with `qm`/`/etc/pve` access.

At enroll time the agent runs the equivalent of:

```
pveum user token add root@pam convoy-<installation-id> --privsep 0
```

and returns `token_id` + `token_secret` in the report. The panel stores them in
the columns it already has — `ProxmoxClient` is untouched. The two attestation
checkboxes disappear, because the thing they attested to is now *constructed*
rather than promised.

Three things this must get right:

- **Idempotency.** The secret is shown only at creation, so a name collision
  means remove-then-add, not "reuse". Name the token after the installation id
  so re-enrolling a rebuilt host is unambiguous.
- **Never hard-fail the enrollment on it.** If `pveum` is missing or refuses,
  the anchor still enrolls and the node lands needing credentials, with the
  paste field as the fallback. A console-capable agent with no API token is
  strictly better than no agent.
- **`ProtectSystem=strict` with no `ReadWritePaths`.** The shipped unit
  (`packaging/systemd/anchor.service:30`) mounts the filesystem read-only with
  no exemptions, so writes to `/etc/pve` are already a live question — as are
  template installs writing to `/var/lib/vz/dump`. This is remaining-work item
  2 in the anchor handoff and credential minting makes it blocking. Add
  `ReadWritePaths=/etc/pve /var/lib/vz/dump` and verify on a real node.

### 4. Enrollment creates the Anchor; approval creates the Node

The split that keeps the schema honest, and the direct answer to "not all
anchors are nodes":

- **Enroll** → `Anchor` row, secret issued, heartbeats start, `approved_at`
  null. The report is parked in a new `anchors.reported_facts` json column.
  The panel refuses to mint console sessions until approved.
- **Approve** (or `auto_approve` on the key, which makes it one step) → the
  `Node` row is created from the parked report plus the location.

The alternative — create the node immediately in a pending state — forces
`nodes.location_id` nullable and puts half-real rows into every node query,
placement scan, and capacity sum. Better to keep the node table meaning
"nodes we actually run servers on" and let the anchor carry the not-yet state.
A relay simply never reaches step two, which is the model saying out loud that
a node is a role rather than a kind of thing.

`nodes.anchor_id` gains a unique index — an agent runs `qm` locally, so it can
only ever serve its own host. Postgres permits many NULLs under a unique index,
so grandfathered rows (below) are unaffected.

### 5. Re-enrollment reconciles instead of duplicating

Key: `(cluster_ca_fingerprint, pve_node_name)`, falling back to
`pve_node_name` for a standalone host. A rebuilt or re-keyed machine matching
an existing node **adopts** it — new secret, same node, same servers — rather
than creating a duplicate that quietly competes for the same VMIDs.
`ClusterIdentityService` already treats the CA fingerprint as the only
trustworthy cluster identity, including its "separated node keeps the old CA"
caveat; reuse that judgment rather than re-deriving it.

Cross-check the report once credentials work: if the agent claims node `pve1`
in cluster X and the PVE API disagrees, flag for a human. The existing
`member_names` tripwire is the precedent.

### 6. `fqdn` and `public_url`

Two different reachability questions, and the agent can only guess at either.

- **`fqdn`** (panel → PVE API): the agent proposes candidates, and the panel
  adds one it can actually trust — **the source IP of the enrollment request**.
  Then it *validates* by connecting, using `NodeConnectionTestService`, before
  accepting. If nothing connects, approval stalls with the candidates listed
  rather than saving a value that looks fine and fails at first use.
- **`public_url`** (panel → anchor): stop writing it into the agent config
  entirely. `grep` shows the agent never reads it — it is panel-side data that
  got mirrored into the TOML. Keeping it panel-only means the panel can correct
  it later without re-enrolling.

### 7. Being honest about "Tailscale style"

This gets Tailscale's *onboarding* ergonomics: one command, no form. It does
**not** get Tailscale's *networking* ergonomics. The relay still dials the
agent's WebSocket URL, so an agent must be inbound-reachable — the very thing
`public_url` exists to describe, and the reason a NAT'd host still needs work.

Removing that means the agent holding a multiplexed outbound connection the
panel dials back through — protocol v2, and a much bigger change. The agent's
heartbeat loop is the natural seam for it. **Keep these sequenced, not merged.**
Enrollment is worth shipping on its own, and conflating them turns a two-week
feature into a protocol rewrite.

Cheap down payment while we're in here: let the heartbeat response carry a
config revision instead of `204`, so the panel can hand down corrected settings
(a reassigned relay, a fixed `public_url`) without an operator re-running
anything.

## Migration from v4 — the constraint is the destination, not the migration's job

A required anchor is where this ends up (see "Getting to required" below). What
it must not be is *the thing the v4 cutover runs into*.

`NOT NULL nodes.anchor_id` applied during the upgrade would force the cutover to
invent a placeholder anchor per node — a row that never heartbeats, never
enrolls, and satisfies the constraint while describing nothing. That is strictly
worse than a null, and it would bake the upgrade moment into the schema forever.
A null says "no anchor yet" accurately, and every part of the panel that needs an
anchor (console sessions, template installs) has to handle its absence anyway,
because an anchor can go offline at any moment.

So for the cutover release the rule is **required by construction, not by
constraint**:

- `nodes.anchor_id` stays nullable, exactly as `2026_07_17_010000` left it.
- Enrollment becomes the only *supported path* for adding a node in the UI —
  "Enroll a node" is the primary action, with "add manually" demoted to an
  escape hatch for hosts that cannot run the agent.
- `POST /api/application/nodes` keeps accepting a full manual payload for now.

The general principle, which the ladder below is built on: **never migrate into
a constraint.** Migrate the population first; add the constraint later, when it
is already a no-op on every row.

### What the v4 operator actually experiences

The cutover in `database/cutover/RUNBOOK.md` is unchanged — pgloader for the
engine, `artisan migrate` for the renames. Nodes arrive with `anchor_id = NULL`
and **keep working**: they poll, they place servers, they run backups. What
they lack is console and template installs, which is exactly what they lacked
in v4 (Coterm, now removed).

Then adoption is the same one command, because §5's reconciliation makes
"enroll a new node" and "adopt an existing one" the same endpoint:

1. The nodes list shows unlinked nodes with an "Install Anchor" action.
2. It issues an enrollment key and shows the command.
3. The agent enrolls, reports `pve_node_name` + cluster fingerprint, matches
   the existing row, and links itself.
4. It mints a fresh PVE token, replacing the v4 credentials the operator
   pasted years ago.

Incremental, per node, no maintenance window, no fleet-wide flag day. An
operator who adopts three of twelve nodes has three nodes with consoles and
nine that work exactly as they did — which is the property that makes it safe
to ship before every operator is ready.

Deliberately **not** doing, at any stage: a migration that auto-creates anchors
to satisfy a constraint. A placeholder anchor encodes the upgrade moment into
the data forever to save an operator one command.

Note the difference between that and stage B's grandfathering below, which is
the opposite move: stage B changes no rows at all, it only stops *new* ones from
being created without an anchor. Grandfathering existing data is fine;
fabricating data to look enrolled is not.

## Getting to required

The goal is a required anchor, for the simplification it buys. The way to get
there without a hazardous migration is a ladder pegged to release boundaries,
where each rung is separately shippable and reversible.

### Stage A — v10.x: enrollment ships, column nullable

Slices 1–5. Unlinked nodes work exactly as they do today and are nagged in the
UI. Add `php artisan anchor:preflight`, which prints how many nodes are
unlinked and the command to fix each. This is the *same* command later stages
gate on, so it earns its keep long before it is load-bearing.

### Stage B — v10.x+n: required by policy

`AnchorSettings::require_anchors` (bool). When on, node creation without an
anchor is rejected — UI and API both — and the manual create form is gone.
Existing unlinked nodes keep running untouched.

The flag gates **creation, not existence**. That distinction is the whole
reason this rung is safe: an operator can turn it on today without auditing
their fleet, because nothing they already have breaks.

Default it by looking at the data, in the migration that adds it:

```php
// Fresh installs get the simple world immediately. An upgraded install with
// unlinked nodes gets the flag off, because turning it on for them would be a
// policy decision made on their behalf about hosts we have never seen.
$default = DB::table('nodes')->whereNull('anchor_id')->doesntExist();
```

That is "required by default" delivered literally: on by default where it costs
nothing, off where it would surprise someone, and a single switch in settings
once they have finished enrolling.

### Stage C — v11.0 (major): required by constraint

`NOT NULL` plus `restrictOnDelete` (replacing `nullOnDelete`). The migration's
job is to **verify, not repair**: it runs the preflight and aborts with the list
of offending nodes and the remediation command if any remain. A major release is
allowed to require work before upgrading; a minor is not. Nothing is auto-created
to satisfy it, ever.

Only after the constraint lands does the code deletion happen — that is the
payoff, and doing it earlier means maintaining both worlds while claiming one.

### What actually gets simpler

Deleted at stage C:

- The manual node form, `StoreNodeRequest`'s credential and spec rules, and the
  two `z.literal(true)` attestation checkboxes.
- `AnchorController::syncNodes()` and `node_ids` on `AnchorFormRequest` —
  assignment stops being an operation; enrollment is the only way a node and an
  anchor meet.
- `Anchor::nodes()` HasMany → HasOne. The `AnchorPicker` on the node form, the
  nullable `anchor_id` filter, and `EnrollmentPanel`'s two entry points collapse
  to one.
- The app-level "Detach this Anchor before deleting it" guard, which the FK now
  enforces.
- `ServerController::console`'s absent-anchor branch, and its equivalents in
  `AnchorSessionService` and the template-install path.

**What does not get simpler, and should not be expected to:** every liveness
branch stays. `compatibility()` still returns UNENROLLED / OFFLINE /
INCOMPATIBLE, and console minting still has to handle "the anchor exists and is
down". *Required is not the same as reachable.* The null check is the small half
of that branching; the health check is the large half and it is permanent.

Test cost is smaller than it looks: 56 `Node::factory()` call sites across 26
files, but `NodeFactory` sets `anchor_id => null` in one place. Point that
default at an agent anchor and most call sites keep working untouched.

### The one real casualty: `POST /api/application/nodes`

Convoy serves **two API surfaces from one set of route definitions**.
`bootstrap/app.php:55-64` loads `routes/api-admin.php` a second time under
`/api/application` behind a Sanctum guard — "one source of truth", in its own
words. So this single line (`routes/api-admin.php:87`):

```php
Route::post('/', [Admin\Nodes\NodeController::class, 'store']);
```

is simultaneously two endpoints:

- `POST /api/admin/nodes` — the browser SPA, session auth
- `POST /api/application/nodes` — **external bearer tokens** holding
  `nodes:write`

The second is a real public endpoint, not a theoretical one: `nodes` is in
`TokenAbilities::RESOURCES`, and the only route in the file that opts out of
token access is `/tokens` (`routes/api-admin.php:365`). Anything integrating
with Convoy — Terraform, provisioning scripts, a billing module — registers
nodes through it today.

**Why stage C necessarily removes it.** Once a node requires an anchor, and an
anchor is by definition *the identity of a daemon that presented a key and
proved it is running*, an API client has nothing to put in that field.
"Create a node" from a JSON payload stops being restricted and starts being
**unrepresentable** — there is no anchor for the payload to reference, and no
honest way to invent one (that is the placeholder-row problem again).

So the constraint that deletes an admin form also deletes a public endpoint.
The form has a replacement the operator can see — run one command. The API
client needs one too, and "run a command" is not available to it.

**The replacement.** The client creates an *enrollment key* and hands it to
whatever builds the host — cloud-init, Ansible, a Proxmox autoinstall answer
file:

```
POST /api/application/anchor-enrollment-keys   →  { token, expires_at }
        ↓ baked into the host build
anchor enroll --panel-url … --token …             (runs on first boot)
        ↓
the node exists, with its specs, credentials and console already correct
```

The automation still works end to end. What changes is the shape: from
*declaring a node* to *authorizing a machine to declare itself*. Terraform users
will find it familiar, because it is what the Tailscale provider does —
`tailscale_tailnet_key`, not `tailscale_device`. And it rides the `anchors`
ability already in `TokenAbilities::RESOURCES`, so it adds an endpoint rather
than a permission vocabulary.

**Why it has to ship a minor early.** There must be one release where *both*
paths work, so integrations can move to keys while `POST /nodes` still answers.
Ship the key endpoint and the removal together and every integration breaks on
upgrade day, with the fix in the same release note nobody read yet. That
overlap window is the real reason stage C is pegged to a major instead of being
folded into stage B.

### Preconditions for stage C

1. **The key endpoint above shipped a full minor earlier**, with the overlap
   window served.
2. **`ON DELETE` flips** to `restrict`.
3. **The FK should only ever point at an agent-mode anchor.** Today that is
   app-level (`syncNodes()` early-returns for relays). A CHECK cannot reach
   another table, so the DB-level options are a trigger or a denormalised mode
   column — neither is worth it. Keep it app-level with an explicit test, and
   note the gap rather than pretending the constraint covers it.
4. **Preflight is documented in the upgrade notes**, not just executed by the
   migration, so an operator meets the requirement before the maintenance
   window rather than inside it.

## Slices

1. **Enrollment keys — DONE.** `anchor_enrollment_keys`, model + derived
   `EnrollmentKeyStatus`, admin CRUD under `/api/admin/anchors/enrollment-keys`,
   and three audit events. Tokens still claim pre-made anchors — no behaviour
   change yet, which is what let it land green on its own (724 pest tests pass).

   Three decisions taken while building it, none of which the plan had settled:

   - **`auto_approve` / `default_location_id` / `default_relay_id` are not in
     the table yet.** They are instructions to an enrollment handler that does
     not exist until slice 2, and a CRUD surface that accepts settings nothing
     honours is an API that lies. They arrive in the slice that reads them —
     `2026_08_02_000000_add_panel_url_override_to_anchors` is the local
     precedent for adding a column with the feature that needs it.
   - **Revoke and delete are separate actions.** `POST /{key}/revoke` withdraws
     the key and keeps the row; `DELETE /{key}` refuses anything still ACTIVE.
     Otherwise deleting is a quieter revocation, and the quiet path is the one
     taken when someone would rather the incident left no roster entry.
   - **The token prefix is the discriminator slice 2 needs.** Keys are
     `anc_key_…`, rotation tokens `anc_enroll_…`, so the enroll endpoint can
     tell "re-key a known installation" from "admit a stranger" without that
     answer depending on a database lookup succeeding.

   Unlimited uses and no expiry are both expressible — a machine image needs
   them — but only by passing an explicit `null`. Omitting either field gives
   the safe shape, so the dangerous one cannot be reached by forgetting a
   parameter.
2. **Self-registration — DONE.** Key-based enrollment creates the `Anchor`
   (`reported_facts`, `approved_at`, `enrollment_key_id`), approval accepts it,
   and session minting gates on approval. Agent gathers and sends the report.

   What the build settled that the plan had not:

   - **Approval had to land in this slice, not slice 3.** Without it a
     self-registered Anchor could never leave the pending state, so the slice
     would have shipped a dead end. Slice 3 now adds *node materialization on
     top of* an approval step that already exists, rather than inventing one.
   - **`anchors.public_url` is nullable.** A machine that just introduced itself
     has not been told how the panel reaches it back. The alternative -- deriving
     a guess from the request's source address -- puts an unvalidated value in
     the column the console dials, where it looks settled and fails at first
     use. Approval is where it stops being null, which is also why approval
     requires it.
   - **`PENDING_APPROVAL` is asked before liveness.** A machine waiting to be
     let in is heartbeating perfectly well; reporting it as unreachable sends an
     operator to the network instead of to the queue holding the decision.
   - **The self-enrollment audit event stayed under `admin.anchor.`** even
     though no admin performed it. Areas are a closed, frontend-matched set, and
     filtering `area=admin.anchor` now returns an installation's whole story.
     That nobody did it is carried by the row having no actor.
   - **The agent does not report the PVE version or the cluster CA
     fingerprint.** Both come back from the Proxmox API over an authenticated
     channel through code the panel already has and tests
     (`ClusterIdentityService`); parsing them out of `/etc/pve` would be a
     second implementation of an answer we can ask for. Host addresses are
     likewise omitted -- the panel records the source address it actually
     observed, which is the one reachability claim a machine cannot overstate.

   **Follow-up worth tracking:** `/api/anchor/enroll` is throttled to 10/minute
   per IP. A rack booting from one image behind a single NAT can exceed that, so
   the agent should back off and retry on 429 rather than the limit being
   raised.
3. **Node materialization.** Approval queue UI; approve → create the node from
   the report + location. `auto_approve` collapses it to one step. Unique index
   on `nodes.anchor_id`.
4. **Credential minting.** Agent creates the PVE token; `ReadWritePaths` fixed
   and verified on a live node; the two attestation checkboxes deleted. Paste
   fallback retained.
5. **Adoption + reconciliation.** Fingerprint/name matching, re-enroll adopts,
   cross-check against the PVE API, unlinked-node nudge on the nodes list, and
   `anchor:preflight`. This is the slice v4 operators need; nothing before it is
   wasted on them.

Stage A ends here. The rungs to required follow on their own release cadence:

6. **Enrollment keys in the Application API** (`POST
   /api/application/anchor-enrollment-keys`) — the replacement for
   `POST /api/application/nodes`, which stage C deletes. Must ship a full minor
   before 8 so both paths overlap for one release.
7. **`require_anchors` setting** — stage B. Gates creation only; defaults from
   the data.
8. **`NOT NULL` + `restrictOnDelete`** — stage C, a major. Verify-and-abort
   migration, then the code deletion listed above.
9. *(separate track)* Reverse tunnel, protocol v2. Not part of this plan.

Slices 1–3 are shippable without 4; an operator who pastes a token still skips
the other thirteen fields. Slices 6–8 are worth starting only once real
installs have run 1–5, because the argument for the constraint is that nobody
is relying on the nullable case any more — and that is an observation, not a
prediction.

## Tests worth writing first

- Key consumption: single-use, `max_uses`, expiry, revoked, wrong `mode`.
- A report cannot set `location_id`, `memory_overallocate`, or the overage
  penalty — assert it directly, since this is the security boundary.
- Re-enroll of a known `(fingerprint, node name)` adopts; an unknown one
  creates; a mismatched claim flags.
- Credential minting failure still yields a usable anchor.
- v4 shape: a node with `anchor_id = NULL` polls, places, and backs up; only
  console and template install decline, with a message naming the reason.

For the later rungs:

- `require_anchors` on rejects anchorless creation via **both** `/api/admin` and
  `/api/application` (one route definition, two guards — a test that only covers
  the admin path proves nothing about the API), and leaves existing unlinked
  nodes fully operational.
- The stage-C migration aborts and changes **nothing** when an unlinked node
  exists — assert the row count and the nullability are both untouched after the
  failure, not just that it threw.
