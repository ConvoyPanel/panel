# Node status and monitoring — plan

Covers GitHub #104 (node resource overview on the admin dashboard, plus
monitoring with email alerts) and the reachability/power indicators wanted on
the Nodes table and the server lists. Written 2026-07-17.

Slices 1 and 2 are implemented (slice 2 on the client list only); slices 3–4 are
designed, not built. Slice 3 (email alerting) is deliberately parked — the
maintainer does not want it yet.

## The problem

Live state is read per row, one PVE call at a time:

- `ProxmoxServerClient::getStatus()` → `/nodes/{node}/qemu/{server}/status/current`,
  **once per server**.
- `NodeStatusController` → `/nodes/{node}/status`, **once per node**.

A 20-row server list would therefore make 20 PVE calls per render. Worse, a node
that is *down* does not fail fast — it burns the full connect timeout, so the
page costs `timeout × N`. Polling per render was never going to work.

## The lever: one call answers everything

`GET /cluster/resources` returns every node **and** every guest in one response:
`type`, `status`, `cpu`, `mem`, `maxmem`, `disk`, `uptime`, `node`.

Measured against the dev node (`us-southeast-2`, PVE 9.2.2):

```
ONE call to /cluster/resources took 158ms and returned 3 rows
  type=node  node=us-southeast-2  id=node/us-southeast-2  status=online  cpu=0.009  mem=1720381440/16766861312
```

Each Convoy `Node` row is a PVE host with its own credentials, so this is **one
call per node, independent of how many servers it hosts**. The server list's
N+1 collapses to "number of distinct nodes", and node reachability arrives in
the same response as the resource figures #104 asks for.

**Trap:** on a real PVE *cluster* this returns the other members' nodes and
guests too. Always filter by `Node::$name` (the PVE node name), or you will
attribute another host's VMs to the wrong Convoy node.

## The principle: the read path never touches PVE

A scheduled poller writes state; the API only reads what is already written.

This is why the read path uses no `Cache::remember` around a PVE call. That
pattern (as `LiveStorageService` uses it, correctly, for a different job) only
moves the stall onto whoever draws the cache miss — and for an unreachable node
that request still eats the full timeout. A miss must render **unknown**, never
trigger a fetch.

## Where state lives, and why it differs

**Node reachability → columns on `nodes`.** Alerting needs a state machine, and
a cache is volatile: losing Redis would re-alert every node on the next tick.
Columns also make status sortable and filterable in the table for free.

| Column | Purpose |
| --- | --- |
| `status` | `online` \| `unreachable` \| `unknown` (`NodeStatus`) |
| `status_code` | the `ConnectionErrorCode` when unreachable — *why*, not just *that* |
| `status_message` | the raw error, for the details disclosure |
| `last_seen_at` | last **successful** contact; drives staleness |
| `status_checked_at` | last attempt, successful or not |
| `consecutive_failures` | debounce counter for alerting (slice 3) |

**Guest power state → cache, one key per node.** `node:{id}:vm-states`, a
vmid→status map (`GuestStateCache`). High-churn, non-critical, and one key per
node beats a row write per server every 30s. TTL is `Node::STATUS_TTL_MINUTES`,
matching the node status it was observed alongside — this originally said "≈ 2×
the poll interval"; see slice 2 below for why it changed.

## Unknown is not stopped

If the poller has not run recently, the UI says **unknown**. It must never
render a running VM as stopped because a cron did not fire — that is worse than
admitting ignorance, because someone will click Start on a machine that is
already up. `last_seen_at` plus the poll interval is what separates "we asked
and it is off" from "we have not asked lately".

## Why not a ping test

#104 proposes monitoring "via ping test". ICMP proves a NIC answered. It does
not prove Proxmox is running, the token is still valid, or the certificate is
trusted — the TLS bug fixed in `edfa75a5` would have sailed through a ping test
while every API call failed. A host that pings but answers `token_invalid` is
**down** as far as Convoy is concerned.

So the check is the authenticated API call we already make, classified through
the `ConnectionErrorCode` the connection test and `NodeUnreachableException`
already share. One vocabulary for "why is this node unhappy", everywhere.

## Slices

**1 — reachability, written by a poller (DONE).**
`NodeStatus` enum, the columns above, `NodeStatusPollService`, `PollNodeStatusJob`,
and `nodes:poll` scheduled every minute. One queued job per node so a dead node's
timeout never serialises behind a healthy one. Nodes table shows the state with
the cause behind it.

**2 — guest power state (DONE, client list only).** `GuestStateCache` (vmid→status
per node), written by the poll, read into `ServerData::$powerState` and rendered by
`PowerStateBadge` on the client server list. **The admin server list still shows
none** — same badge, not yet wired.

Correcting this section's own claim of "same response, no new call": slice 1
shipped against `/nodes/{node}/status`, which does not return guests, so slice 2
had to **switch the poll to `/cluster/resources`** — the endpoint the lever above
always assumed. Reachability now means "that call answered". Adding it as a second
call was the alternative, and was rejected: a second endpoint is a second timeout
to sit through on exactly the nodes that are down.

Two deviations from what this document specified, both deliberate:

- **TTL is `Node::STATUS_TTL_MINUTES` (5), not "2× the poll interval" (2).** Both
  facts come from one response, so they have to lapse together — a node still
  reading `online` beside guests already `unknown` is an inconsistency a viewer
  can only read as a bug.
- **A failed poll leaves the map alone** rather than clearing it. One failure is
  not evidence a guest changed state; the map stands until it expires on its own.

**3 — alerting (#104's second half).** Notify on the `online → unreachable`
transition only, debounced behind `consecutive_failures >= N` so a flap does not
page anyone, plus a recovery notice on the way back. Laravel notifications over
the existing mail config.

**4 — dashboard overview (#104's first half).** CPU/RAM/disk per node on the
admin dashboard, read from the poller's snapshot. `metrics:snapshot` +
VictoriaMetrics already exist for history and sparklines; the poller covers
"now".

## Risks

- **The scheduler must actually run.** `schedule:run` is already required for
  bandwidth quotas, so this adds no new dependency — but if it is not running,
  every node reads `unknown` forever. That is the honest failure, and it is why
  `unknown` is a first-class state rather than a bug.
- **Many nodes.** One job per node keeps failures isolated and parallel, but a
  large install wants the queue workers to keep up; the poll is idempotent and
  `withoutOverlapping` prevents pile-up.
- **Poll interval vs timeout.** The per-node timeout must stay well under the
  interval or a wholly-offline fleet will never finish a pass.
