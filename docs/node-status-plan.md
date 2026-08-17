# Node status and monitoring — plan

Covers GitHub #104 (node resource overview on the admin dashboard, plus
monitoring with email alerts) and the reachability/power indicators wanted on
the Nodes table and the server lists. Written 2026-07-17.

Slices 1, 2, 4 and 5 are implemented. Slice 3 (email alerting) is deliberately
parked — the maintainer does not want it yet, reaffirmed 2026-08-17.

**#104 therefore cannot be closed on slice 4 alone.** The issue asks for a
resource overview *and* an email when a check fails; the first half is shipped
and the second is a standing decision not to build. Say that when closing rather
than implying the request was met in full.

## The problem

Live state is read per row, one PVE call at a time:

- `ProxmoxServerClient::getStatus()` → `/nodes/{node}/qemu/{server}/status/current`,
  **once per server**.
- `NodeStatusController` → `/nodes/{node}/status`, **once per node**.

A 20-row server list would therefore make 20 PVE calls per render. Worse, a node
that is *down* does not fail fast — it burns the full connect timeout, so the
page costs `timeout × N`. Polling per render was never going to work.

## The lever: one call answers everything

`GET /cluster/resources` returns every node, every guest **and** every datastore
in one response: `type`, `status`, `cpu`, `mem`, `maxmem`, `disk`, `maxdisk`,
`uptime`, `node`, `storage`, `shared`.

Note `disk`/`maxdisk` change meaning with `type`: used/total bytes on a
`storage` row, the guest's root image on a `qemu` one. Same keys, different
question — read them only off the row type you meant.

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

**2 — guest power state (DONE).** `GuestStateCache` (vmid→status per node),
written by the poll, read into `ServerData::$powerState` and rendered by
`PowerStateBadge` on the client server list, the admin server list, and a node's
own server list.

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

**4 — dashboard overview (#104's first half) (DONE).** CPU/RAM/storage per node
on the admin dashboard (`NodesCard`), read from the poller's snapshot
(`NodeResourceSnapshotCache`) and never fetched on the read path.

Storage covers **every datastore, not just the host's root filesystem** — #104
asks about "the installed hard disks", plural. The figures come from the
`type=storage` rows of the same `/cluster/resources` response the poll already
makes, which were previously decoded and discarded, so this costs no extra call
and no extra timeout on a node that is down. Rows are filtered by `Node::$name`
(the cluster trap again — a shared store is reported once per node that mounts
it).

The dashboard shows them **summed into one figure per node**, not a meter each.
A meter per store makes the cell grow without bound — a host with a dozen
datastores turns one table row into a dozen meters, on a card meant to be read
at a glance across a fleet. The per-store breakdown belongs on the node's own
Storages tab, where there is room for it.

The sum is over raw bytes, not a mean of percentages: averaging would let a full
10 GiB scratch store weigh as heavily as a half-empty 20 TiB array. Stores PVE
could not read are **excluded from the sum** rather than counted as empty — an
unmounted export reports 0/0 and would quietly deflate everything around it — and
`unreadableDatastores` is how the card admits the total is incomplete.

`$datastores` still carries the per-store breakdown (sorted fullest-first) even
though the dashboard no longer draws it; it is there for a future Storages tab
that reads the poller's snapshot instead of calling PVE per request the way
`LiveStorageService` still does.

Two notes for whoever touches this next:

- `NodeResourceSnapshotData::$datastores` is a `DataCollection`, not an array of
  `Data`. A plain array picks up the `data` wrapper when serialised through the
  response, so the JSON came out as `datastores.data[]` while the generated
  TypeScript said `NodeDatastoreUsageData[]`.
- The snapshot cache key carries a `:v2` suffix. Cached `Data` objects are
  unserialised without running the constructor, so adding a property leaves rows
  written by the old shape uninitialised and they throw on first read. Bump the
  suffix when the shape changes; the old rows then simply expire.

**Node detail page.** `NodeStatusController` (`/nodes/{node}/status`) is the one
read path that still calls PVE per request, because `/cluster/resources` does not
carry the CPU model, kernel, boot mode or PVE version that page shows. It is
gated on the stored status: a node already recorded `unreachable` is not asked at
all (neither on mount nor on the 30s refetch), and the page renders the cause the
poller classified, behind a "Check anyway" button. `unknown` still fires — nobody
has asked yet, and one call for one node is a reasonable way to find out.

**5 — the storage model (DONE, except the global list).** Everything on
`storages` was operator-declared and unchecked, so any of it could drift. The
poll now records what PVE says beside it — `pve_type`, `pve_shared`,
`pve_content` and capacity — from the `type=storage` rows of the
`/cluster/resources` response it already makes, so discovery costs no request and
no timeout on a node that is down.

`pve_type` is the load-bearing one. It separates a thin backend, where committed
legitimately exceeds written bytes, from a thick one where the same gap is space
nobody can account for — and it identifies a Proxmox Backup Server datastore,
which needs **no model of its own**. PBS is an ordinary storage in PVE too
(`type: pbs`, `content: backup`, marked shared, reached entirely through PVE), and
its three apparent caveats are the general `content`, `thin` and `shared`
properties every other backend already needs. `Support\StorageBackends` owns the
thin list, shared by the cluster DTO and the Eloquent side so they cannot drift.

The storages tab reads capacity live → recorded → unknown and says which, so an
unreachable node costs freshness rather than the whole panel. `untracked` is
*withheld* rather than clamped on thin and deduplicating backends: the old
`max(0, ...)` turned a negative into zero and presented "nothing unaccounted for"
as a finding when it was an artefact of arithmetic that does not apply there.

**Clusters.** `nodes.cluster_name` comes from `/cluster/status`, asked only after
`/cluster/resources` has already succeeded — a second endpoint is a second
timeout on a node that is down, and a test asserts the call is never made on an
unreachable one. Null means standalone, which is PVE's own answer rather than a
gap.

That is what makes shared storage expressible. A storage may now be attached to
several nodes, refused unless the nodes share a cluster (`storage.cfg` is
cluster-wide, so a storage id means nothing across clusters) and unless PVE
actually reports it on the target — taking the operator's word for it is the same
mistake as trusting a hand-set `shared` flag. Rows name the other nodes they
reach, because "shared" as a badge does not tell a reader that 20 TiB of free
Ceph on four nodes is 20 TiB in total.

Four pivot writes were fixed first, all one bug: `storage_to_node` declares
`storage_id` as its primary key, so with two nodes there are two rows answering
to it. `update()`, `updateBackupOrder()`, `buildSortQuery()` and `destroy()` are
now all node-scoped, and `destroy()` detaches rather than deleting when others
still reach the pool.

**The global storage list (DONE).** Storage is a top-level Provisioning item
beside IPAM, which is already both a global inventory and a node tab — the same
shape storage took once a pool could be attached to several nodes. The node tab
stays and keeps its own job: registering a host's disks, and answering whether
this host can take another server.

`StorageInventoryController` makes **no PVE call at all**. The node-scoped list
can afford one live lookup because it is one node; a fleet page would make one
per node, and a single unreachable host would stall the whole list for a full
connect timeout. It reads the figures the poll already writes, and a test asserts
no stray HTTP. Storages no node reaches are omitted, matching what
`OverviewService::storage()` already counts as fleet capacity.

**History.** Per-node CPU/RAM/disk over time should come from PVE's own RRD store
(`GET /nodes/{node}/rrddata`, the node-scoped sibling of what
`ProxmoxStatisticsClient` already calls for servers), *not* from per-node series
in VictoriaMetrics. PVE consolidates with `AVERAGE` server-side; `metrics:snapshot`
is hourly and fleet-wide, and hourly sampling of an instantaneous CPU reading is
noise rather than a trend. Not built.

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
