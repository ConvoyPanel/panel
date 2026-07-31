# Node → Network redesign — handoff

Status as of 2026-07-25. **Direction D (topology tree) is implemented**, on top of a new `vlans`
table. Backend, API, types and UI are in; the full backend suite is green. What remains is listed
under "Not done" at the bottom.

Mocks that started this (some of what they showed was fiction — see below):
<https://claude.ai/code/artifact/cbe7a131-75af-4726-9db1-7287f4c3639f>

## The problem it solved

`network.lazy.tsx` rendered an `ItemGroup` of full-width bars holding three words each. The loudest
element stated an absence ("Not VLAN-aware", the default for almost every bridge), nothing anchored
the right edge, and it never answered the operator's actual question — *what can I attach a server
to, and is anything on it?*

Four directions were mocked (DataTable / framed list / tiles / topology). **D was chosen**, with the
two fixes that applied regardless: retire the "Not VLAN-aware" badge (now muted *Untagged* text,
badges reserved for the exception) and add a server count.

## What a VLAN is now

Before this change a VLAN had **no identity**: it was an integer in two columns, and existed only
because some server happened to carry it. That is why the mock's VLAN names, ranges and
declared-but-unused VLANs were unbuildable.

`vlans` (`2026_07_25_000000_create_vlans_table.php`) is a **registry**, deliberately not the source
of truth for what Proxmox is told:

| Column | Meaning |
|---|---|
| `network_interface_id` | cascade-deleted with the bridge |
| `tag` | 1–4094, unique **per interface** (the same tag on two bridges is two VLANs) |
| `name`, `description` | nullable — the thing the old schema had nowhere to put |

**Tag resolution is unchanged.** `ServerNetworkService.php:57` still reads
`$server->vlan_tag ?? $networkInterface->vlan_tag` on an aware bridge, and `servers.vlan_tag` is
still a bare smallint. Declaring a VLAN moves no server; deleting one detaches nothing.

The migration backfills a declaration for every tag already resolvable (bridge defaults plus
distinct server tags on aware bridges), so existing fleets don't render as empty trees. Pinned by
`tests/Feature/Models/VlanBackfillTest.php`.

### Undeclared VLANs

Server tags are validated for range and bridge-awareness, not against the registry, so a server can
carry a tag no `vlans` row describes. Those surface as `VlanData` with **`id: null`** and an
*Undeclared* badge, with a "Declare" action that adopts the tag. Hiding them would hide the servers
on them. Declaring an in-use tag replaces the undeclared entry rather than duplicating it; deleting
a declaration that still has members reverts it to undeclared.

### The inheritance trap, handled

`NetworkInterface::vlanUsageFor()` groups on `COALESCE(servers.vlan_tag, network_interfaces.vlan_tag)`,
**not** `servers.vlan_tag`. A server on an aware bridge with a null tag inherits the bridge default;
grouping naively files it under "untagged" while Proxmox has it on the bridge's tag. Filtering on
the coalesced value is also what correctly excludes untagged servers on a *pure* trunk (aware, no
default) — they belong to no VLAN. Pinned by `NetworkInterfaceVlanUsageTest.php`.

`is_vlan_aware = true` with `vlan_tag = null` is legal and rendered as such.

## Shipped

**Backend**
- `vlans` table + backfill; `Vlan` model; `NetworkInterface::vlans()`, `servers()`, `vlanUsage()`,
  `vlanUsageFor()` (batched — the list resolves usage in one query, not one per interface).
- `NetworkInterfaceData` gained `serversCount`, `addressPoolsCount`, `vlans`.
- `VlanController` CRUD nested at
  `/api/admin/nodes/{node}/network-interfaces/{network_interface}/vlans`. The route group's
  `scopeBindings()` keeps a VLAN from being reached through another interface.
- Turning `is_vlan_aware` off deletes that bridge's VLANs, alongside the server-tag clearing that
  already happened — nothing could resolve to them any more.
- `NetworkInterfaceFactory` (with a `trunk()` state) and `VlanFactory`.

**Two traps worth knowing about, both now covered by tests**
- Every write path must return the same derived fields the index does (`respondWith()`). The client
  merges write responses straight into its cached list, so an update that omitted `vlans` or a count
  would blank them until the next refetch.
- Nested `DataCollection`s serialize bare inside a collection but pick up the global `data` wrap when
  returned as a single resource. `vlans` is explicitly `->withoutWrapping()` so index and update
  agree; without it the update response arrives as `vlans.data`.

**Frontend** — `NetworkInterfaceCard.tsx` is now a tree row: non-trunks render as a plain line (no
dead expander), trunks disclose their VLANs. `VlanFormModal` (create + edit share one form) and
`DeleteVlanModal`. Rows sit in one framed `Card` rather than a stack of cards.

Verified in the running app: declare-adopts-tag, delete-reverts-to-undeclared, delete-empty-removes,
light and dark, no console errors, no horizontal overflow at 1440 / 480 / 420 px.

## Not done

- **`servers.vlan_tag` is still an integer, not an FK to `vlans.id`.** Registry-first was a
  prerequisite either way (rows must exist before anything can point at them). Converting it would
  give referential integrity and make "undeclared" impossible, at the cost of reaching into
  `ServerNetworkService`, `StoreServerRequest`, `UpdateBuildRequest` and the server build UI, plus a
  policy for what deleting a VLAN does to its servers.
- Server create/update still doesn't validate a tag against the registry — deliberate, so declaring
  isn't forced on anyone, but it's the reason undeclared VLANs exist at all.
- No frontend tests; the VLAN flows were verified by driving the real app.
- Address pools show as a **count** only. `NetworkInterface::addressBlockGroups()` could list them
  per bridge if "which subnets live here" turns out to be the more useful answer.
