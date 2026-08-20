# Templates — handoff

Where the template work stands, what was built, what was thrown away, and why.
Supersedes [cofoundry-templates-handoff.md](cofoundry-templates-handoff.md),
whose design is no longer the plan. Written 2026-08-19.

**Status: designed, not built.** Working code exists on two branches, but it
implements the *rejected* model. Read "What was built" before reusing any of it.

## The ask

Let an operator install prebuilt VM templates without shelling into a node, and
update them when a newer build lands. Convoy publishes such templates via
[Cofoundry](https://github.com/ConvoyPanel/cofoundry) — but Cofoundry is one
*source*, not the shape of the feature. Operators with their own templates are
the more common case and must be first-class.

## What was built (and why it is wrong)

Two branches, both green, both implementing a model we have since rejected:

- **panel** `feat/templates-cofoundry-import` (`74e4b724`) — registry service,
  catalog, import planner, `template_installs` table, polling job, admin
  endpoints, frontend data layer. 31 files, ~2900 lines, 26 tests.
- **anchor** `feat/templates-install` (`7f7b8f4`) — a `templates.install`
  capability: download → verify SHA-256 → `qmrestore` → `qm template`. 39 tests.

The **anchor side is sound and worth keeping essentially as-is.** Its install
spec is `{name, url, sha256, size, vmid, storage, overwrite}` — it has no idea
what Cofoundry is, which is exactly right. Two details in it are load-bearing
and were expensive to learn:

- Cofoundry `vzdump`s a *stopped VM*, not a template, so the restore must be
  followed by `qm template <vmid>`. Convoy only offers guests reporting
  `template: 1`; without this an install appears to succeed and stays invisible.
- VMIDs are cluster-global while `/etc/pve/qemu-server` is a symlink to the
  local node's directory, so conflict detection reads pmxcfs's `.vmlist`. It
  runs *before* the download — the alternative is learning about a collision
  from `qmrestore` after several gigabytes.

The **panel side has the wrong model.** It fans out one install per node, all
pinned to a single VMID, because `templates.vmid` is one panel-wide column. That
is correct only for a fleet of standalone hosts. On a PVE cluster the second
node's restore is refused for a VMID already taken cluster-wide, so one install
succeeds and the rest fail.

Trying to model around that produced a "placement scope" rule and a
cluster/storage matrix (published as an artifact, now retired). That was the
wrong direction: it re-derived reachability the storage layer already knows.

## The model to build instead

Two tables. A template is *what a user picks*; a VMID is *where the bytes
landed*. Those are different facts and stop sharing a row.

```
templates            group_id, name, description, is_admin_only,
                     source, source_ref, preferred_vmid?
                     -- no vmid column

template_instances   template_id, storage_id, node_id, vmid,
                     state, progress, error, checksum, installed_at
                     -- one row per template that really exists somewhere
```

`template_instances` replaces both the `vmid` column and the separate
`template_installs` table: the in-flight install and the finished fact are the
same row at different points in its life. It also makes update state per-node,
which the single `source_sha256` column could not express.

**Reachability is a join, not a table.** "Which nodes can use this template?" is
`$instance->storage->nodes` — see the storage section below. Consequences:

| Situation | Rows | Why it just works |
| --- | --- | --- |
| Cluster, shared pool | 1 instance | The pool is already linked to every member |
| Cluster, local pools | 1 per node | Local pools are deliberately not linked across nodes |
| Standalone hosts | 1 per host | Separate id spaces; VMIDs may collide harmlessly |

The template code therefore never asks what a cluster is.

### VMIDs: one optional hint, N recorded facts

Neither "same VMID everywhere" (an assumption that breaks on clusters) nor
"specify one per node" (tedious when they are all the same). Instead:

- By default nobody types a VMID. Anchor reads the cluster-wide `.vmlist`,
  takes the first free id, and **reports back what it used**; the panel records
  it on the instance. This requires making `vmid` optional in the install spec —
  the one change needed on the anchor branch.
- `templates.preferred_vmid` is an optional house-convention hint, honoured
  where free, with the drift recorded where it is not.

Cost of dropping the single `vmid` column: four call sites, each becoming "this
template's vmid *on this node*" —

- `app/Services/Proxmox/Server/ProxmoxServerClient.php:51` (the clone source)
- `app/Services/Servers/ServerCreationService.php:165` (availability check)
- `app/Actions/Server/BuildServerAction.php:76` (sizing the template)
- `app/Data/Server/Templates/TemplateData.php:29` (API output)

### Three ways a template arrives

`templates.source` is an enum; Cofoundry is a default config value, not a
concept in the code. Rename `CofoundryRegistryService` → `TemplateRegistryService`.

- **`manual`** — point Convoy at a VMID that is already a template on a node.
  No download, Anchor not involved, no update concept. This is today's flow and
  it stays.
- **`url`** — any vzdump artifact the operator hosts, plus an optional checksum.
  Nearly free: the anchor spec is already exactly this.
- **`registry`** — browse a catalogue. Cofoundry's `registry.json` is the
  default URL; anyone can serve the same schema. Identical path to `url`,
  pre-filled.

Worth adding: **promote an existing server to a template** (`qm template`, no
download), which is what operators with their own images will reach for first.

"Update available" means something only for `url` and `registry`. A manual
template simply *is* — that should be explicit, not a null-shaped edge case.

## How Convoy's storage layer is wired

Necessary context, because templates hang off it entirely.

`nodes:poll` runs every minute (`routes/console.php:49`), one job per node:

1. **Operator registers** a storage by name under one node.
   `StorageController::store()` creates the `storages` row and **one**
   `storage_to_node` link.
2. **Poll discovers** — `StorageDiscoveryService` matches PVE's `type=storage`
   rows by name *within that node's own storages* and writes `pve_type`,
   `pve_shared`, capacity.
3. **Poll adopts** content flags — `stores_*` is overwritten from PVE every
   minute, since `storage.cfg` is the real authority.
4. **Poll fans out** — `SharedStorageLinkService` links a `shared` pool to every
   cluster member PVE reports it on. Local pools are deliberately excluded: the
   same name on three hosts is three different disks.

`cluster_name` is **autofilled, never typed**. `ProxmoxClusterStatusClient`
reads `/cluster/status` and takes the `type=cluster` row's name; no such row
means standalone. It is asked only after `/cluster/resources` has already
succeeded, so a dead node costs one timeout rather than two, and a failure keeps
the previous value.

### Two seams, since resolved (2026-08-20)

Both seams this section used to describe are fixed on `next`; templates
inherit the fixed model. What changed:

**Cluster identity is a real entity now.** A `clusters` table holds one row
per PVE cluster, identified by the cluster CA's fingerprint (read from
`/nodes/{node}/certificates/info`; the CA lives in pmxcfs, so all members
share it and no two clusters can) — plus one singleton row per standalone
node, keyed to the node and deliberately *not* to its certificate: a node
separated without a reinstall keeps the old cluster's CA, and
`/cluster/status` carrying no `type=cluster` row is what standalone means.
`nodes.cluster_name` is gone; `nodes.cluster_id` replaced it, resolved by
`ClusterIdentityService` from the poll (and immediately at node creation).
A member set reported wholly disjoint from the recorded one flags the row
(`flagged_at`) for a human instead of silently rewriting it — the tripwire
for the one case even a certificate can fake (dirty-separated node
re-clustered on the old CA).

**A `storages` row is one `storage.cfg` definition.** `storages.cluster_id` +
`unique (cluster_id, name)` make duplicate registration structurally
impossible; registering the same pool through a second node attaches instead
of duplicating. Per-mount observations (`discovered_*`) moved onto
`storage_to_node`, so one local definition on N nodes carries N honest
readings. `SharedStorageLinkService` became `StorageLinkSyncService`: it
still fans out only `shared` pools, and now also prunes confirmed links that
PVE (speaking for an online member) no longer reports. When a node's scope
changes, `ClusterIdentityService` re-homes it — a singleton's storages merge
by name into the discovered cluster (this is also how v4's per-node rows
converge after upgrading), while a node leaving a cluster takes nothing.

The v4 upgrade path is covered: migrations `2026_08_20_*` backfill each node
into a singleton scope (preserving v4 semantics exactly) and the first poll
merges real clusters, so no data migration ever guesses identity from a name.

## Recommended sequencing

Reachability no longer needs a narrow v1: `$instance->storage->nodes` is
correct from day one for shared pools, and for a local definition the
instance's own `node_id` is the answer (`pve_shared` says which case you are
in).

1. `templates` + `template_instances`; drop `templates.vmid`, update the four
   call sites. Manual source only — this alone replaces today's flow.
2. Anchor: make `vmid` optional in the install spec and report the chosen id.
3. `url` source end to end (download, verify, restore, record).
4. `registry` source on top of 3; Cofoundry as the default URL.

## Also unresolved

- **Naming.** `template_instances` is the current pick. `template_copies` is
  plainer, `template_guests` is PVE-native. Avoid `template_placements` (tried,
  rejected — it read as a join table for a relationship that already exists) and
  `node_templates` for the same reason.
- **The frontend was never built.** Only `features/template-import/api.ts`
  exists on the branch. No modal, no progress list, no update badge.
- **PHPStan never completed** on the branch — it segfaulted twice in the dev
  sandbox, so the panel side is lint-clean via Pint but statically unverified.
