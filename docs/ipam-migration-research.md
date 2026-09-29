# IPAM across migration and import: design research

Reference for GitHub #162 (Server Importing) and the unfinished half of #161
(Server Transfers & Ceph Reconciliation). Written 2026-09-20. Research only;
nothing here is built.

Both features have the same hard part: an address in Convoy is reachable from
some nodes and not others, and neither `node_id` changing nor a guest arriving
from outside the panel currently touches the address rows at all.

---

## 1. How an address is bound today

### The chain

```
addresses.address_block_id  ->  address_blocks.address_block_group_id
                            ->  address_block_groups
                            <-  address_block_group_to_network_interface
                            ->  network_interfaces.node_id  ->  nodes.cluster_id
```

An `Address` has exactly two bindings, both on its own row:

| Column | Meaning |
|---|---|
| `address_block_id` | Not nullable, `cascadeOnDelete`. The address's identity: version, gateway and MAC are all accessors that read the parent block (`Address::getVersionAttribute`, `getGatewayAttribute`, `getMacAddressAttribute`). |
| `server_id` | Nullable, the tenancy. Kept in lock-step with `state` (`available` / `assigned` / `reserved`) and `state_reason` (`system` / `admin`). |

There is **no `node_id` and no `network_interface_id` on `addresses`**, and
none on `address_blocks` or `address_block_groups` either. `ip` and
`address_blocks.base_ip` / `gateway` are Postgres `inet`
(`2026_07_07_000000_convert_ip_columns_to_inet`).

### Scope of each table

| Object | Scope | How the scope is expressed |
|---|---|---|
| `addresses` | Global | Reached only through its block. |
| `address_blocks` | Global | `address_block_group_id`. Geometry is `base_ip`, `prefix_length_from` (the block) and `prefix_length_to` (the allocatable unit). |
| `address_block_groups` | Global **definition**, node-scoped **reachability** | Reachability is exactly the set of nodes owning an interface in `address_block_group_to_network_interface`. A group attached to interfaces on three nodes is reachable from three nodes. Nothing constrains those nodes to one cluster. |
| `network_interfaces` | Node | `node_id`. Rows are hand-declared by an admin (`NetworkInterfaceController::store`), never discovered from PVE. |
| `vlans` | Interface | `unique (network_interface_id, tag)`. A registry only; the tag actually written is `COALESCE(servers.vlan_tag, network_interfaces.vlan_tag)`. |
| `storages` | **Cluster** | `storages.cluster_id` + `unique (cluster_id, name)`, instantiated per node through `storage_to_node`. This is the one place in the schema that already models "one definition, many nodes". |
| `servers` | Node | `node_id`, `network_interface_id` (`nullOnDelete`), `vlan_tag`, `primary_ipv4_address_id`, `primary_ipv6_address_id`, `smbios_uuid`, `flagged_at` / `flag_reason`. |

`Node::addresses()` is a five-hop `HasManyDeep`
(`network_interfaces` → pivot → groups → blocks → addresses). A node's
addresses are therefore a **derived reachability query, not ownership**. This
is the right shape and should not change.

### Where the invariant is enforced

The rule "an address may only be assigned to a server whose node has an
interface attached to that address's group" exists in exactly one place:
`App\Http\Requests\Admin\Addresses\UpdateAddressRequest`, as a closure inside
the `server_id` rule. `AddressAllocationService::handle()` enforces the same
thing structurally by taking a `network_interface_id` and only considering
blocks under groups attached to it.

Both are **creation-time checks**. Nothing re-checks the invariant afterwards.

### What breaks when `servers.node_id` changes

`ServerPlacementService::rehome()` (shipped under #161) already re-homes a
server after an HA recovery or an out-of-band `qm migrate`. It:

1. rewrites `servers.node_id`,
2. repoints `servers.network_interface_id` to the interface **with the same
   bridge name** on the target node, clearing it and flagging the server when
   no such bridge exists,
3. clears `flagged_at` / `flag_reason` on a clean re-home.

It never looks at `addresses`. Consequences, in order of severity:

- **Silent unroutability.** The same bridge name on two nodes can be attached
  to different address block groups. Re-home matches on name, finds an
  interface, does not flag, and the server now holds addresses whose group is
  not reachable from its node. `UpdateAddressRequest`'s invariant is violated
  and nothing notices.
- **Active mis-write.** `ServerNetworkService::syncSettings()` then writes
  `ipconfig0` (`CloudinitService::setIpConfig`) and the NIC's `bridge` from
  those stale addresses. `syncSettings` refuses only when `flagged_at` is set,
  which case 1 does not set. A `SyncNetworkSettingsJob` after a migration can
  therefore push an unroutable IP onto the guest. Worse, the cloud-init drive
  is regenerated on the destination from those same config values, so a guest
  that reboots after the migration re-applies the old address on its own.
- **Listing drift.** The server's addresses drop out of the source node's
  `Node::addresses()` and do not appear in the destination's.
- **No release path.** If the IP genuinely cannot follow, nothing releases it.
  `syncAddresses()` exists and does the right thing (`assigned` → `available`),
  but no caller invokes it on a placement change.

There is also a half-built import path already in the tree:
`StoreServerRequest` accepts `should_create_vm: false`, and
`ServerCreationService` then records `DeploymentType::IMPORT`. It is a
node + VMID form, not an import. The server row is built from operator input,
and the subsequent `VmSyncService` run stamps `smbios1` and **overwrites** the
guest's `ipconfig`, NIC bridge, MAC and firewall from panel state. Adopting an
existing guest through it destroys that guest's networking.

---

## 2. Prior art

### Proxmox VE

Facts taken from the repo's own captured API surface
(`docs/pve-api/endpoints.ndjson`, `docs/pve-api/markdown/endpoints/`) and the
upstream docs.

- **`POST /nodes/{node}/qemu/{vmid}/migrate`** takes `target`, `online`,
  `targetstorage` (a storage-pair list), `with-local-disks`, `force`,
  `migration_network`, `migration_type`, `bwlimit`, `with-conntrack-state`.
  **There is no bridge-remapping parameter.** Intra-cluster migration assumes
  the destination has an identically named bridge.
- **`GET /nodes/{node}/qemu/{vmid}/migrate`** ("Get preconditions for
  migration") returns `allowed_nodes`, `not_allowed_nodes` (with
  `unavailable_storages` and `blocking-ha-resources`), `local_disks`,
  `local_resources`, `mapped-resource-info` and `running`. This is a
  ready-made preflight feed for a confirmation screen.
- **`POST /nodes/{node}/qemu/{vmid}/remote_migrate`** is the only endpoint with
  bridge remapping: `target-bridge` ("Mapping from source to target bridges"),
  plus `target-endpoint`, `target-storage`, `target-vmid` and `delete`.
  Cross-cluster only, and experimental.
- **Live migration preconditions** (`qm.adoc`, "Migration"): same cluster, CPUs
  "from the same vendor with similar capabilities", same CPU architecture,
  target running the same or newer PVE packages, and no local resources that
  block it: "PCI or USB devices that are passed through currently block
  live-migration". `GET .../migrate` reports all of these as
  `not_allowed_nodes` / `local_resources`, so the panel does not have to
  re-derive them.
- Migration moves the config file and the disks. It does not change the guest's
  MAC, its `ipconfigN`, or anything inside the guest. One caveat that matters
  here: the **cloud-init drive is regenerated on the target from the config
  values, not block-copied** (the same special handling TPM state disks get).
  A stale `ipconfigN` therefore does not merely persist across a migration; it
  is re-baked into a fresh cloud-init image on the destination. A bridge
  missing on the destination surfaces as the `pve-bridge` hook failing, or as a
  VM that migrates offline and then refuses to start
  ([forum: pve-bridge failed](https://forum.proxmox.com/threads/migration-error-pve-bridge-failed-with-status-6400.88703/),
  [forum: network fails after migration](https://forum.proxmox.com/threads/network-fails-on-vm-cts-after-migration-to-another-node.42355/)).
  The operative expectation is that same-named bridges on different nodes are
  the same network, which is exactly the assumption `ServerPlacementService`
  already encodes.
- **Cluster-wide vs node-local.** pmxcfs replicates `/etc/pve` to every node,
  so VMID uniqueness, `storage.cfg` and the whole SDN config are cluster-wide. A
  node joining a cluster "will inherit the cluster's storage configuration"
  and is then expected to have each storage's node restriction adjusted to
  reflect where it is actually available. Bridges in
  `/etc/network/interfaces` are **not** in pmxcfs; each node manages its own.
  This asymmetry is the entire problem: storage is already modelled
  cluster-first in Convoy (`storages.cluster_id` + `storage_to_node`, the exact
  shape of PVE's own "one definition, node restrictions"), networking is not.
- **Config string formats** (`man qm.conf`,
  <https://pve.proxmox.com/wiki/Manual:_qm.conf>):

  ```
  net[n]: [model=]<enum> [,bridge=<bridge>] [,firewall=<1|0>] [,link_down=<1|0>]
          [,macaddr=<XX:XX:XX:XX:XX:XX>] [,mtu=<int>] [,queues=<int>]
          [,rate=<number>] [,tag=<int>] [,trunks=<vlanid[;vlanid...]>]
  ipconfig[n]: [gw=<GatewayIPv4>] [,gw6=<GatewayIPv6>] [,ip=<IPv4/CIDR>] [,ip6=<IPv6/CIDR>]
  ```

  `ip` also accepts the literal `dhcp`; `ip6` also accepts `auto`. Convoy
  already has codecs for both: `App\Data\Server\Proxmox\Config\NetworkDeviceData`
  (with an `extraProperties` bag that round-trips unknown sub-keys) and
  `IpConfigData`.
- **Discovery surface for import**: `/cluster/resources` (already polled every
  minute by `NodeStatusPollService`, already cached in `GuestStateCache`, and
  already carries `vmid`, `node`, `template`, `lock`),
  `GET /nodes/{node}/qemu/{vmid}/config` for `netN` / `ipconfigN` / `smbios1`,
  and `GET /nodes/{node}/qemu/{vmid}/agent/network-get-interfaces` for what the
  guest actually has (requires `VM.GuestAgent.Audit`).
- **SDN** (<https://pve.proxmox.com/pve-docs/chapter-pvesdn.html>) is the
  upstream answer to "make the IP routable from every node". Zone types:
  Simple (node-local NAT bridge), VLAN, QinQ, VXLAN ("Layer 2 VXLAN network via
  a UDP tunnel"), EVPN ("VXLAN with BGP to establish Layer 3 routing"). EVPN
  gives every VNet an anycast gateway MAC and "the bridge IP is the same on
  each node", with `exitnodes` announcing the default route. That is the
  configuration under which an IP genuinely follows a VM anywhere in the
  cluster. Simple zones are the opposite: "VM traffic is only local on each
  node". `POST /cluster/sdn/zones` takes `ipam` (a named backend) and `dhcp`;
  `POST /cluster/sdn/vnets/{vnet}/ips` creates an IP-to-MAC mapping.
  The three shipped IPAM backends are **the built-in PVE IPAM, phpIPAM and
  NetBox** (plugins under `/usr/share/perl5/PVE/Network/SDN/Ipams/`), and DHCP
  is dnsmasq driven from IPAM's MAC↔IP mappings. **PVE 8.1 is the version to
  gate on**: it is where DHCP integration landed and where SDN became installed
  by default. PVE's docs still call the IPAM/DHCP integration "in tech preview"
  while "core SDN ... is fully supported".

### OpenStack Neutron: the canonical split

Neutron separates the address from the host binding on the same object. A
`port` carries `fixed_ips` (`[{subnet_id, ip_address}]`), `mac_address` and
`network_id`; binding to a hypervisor lives in `binding:host_id`,
`binding:vif_type`, `binding:vnic_type`, `binding:profile`,
`binding:vif_details`
([ports API](https://github.com/openstack/neutron-lib/blob/master/api-ref/source/v2/ports.inc)).
Live migration rewrites `binding:host_id` and never touches `fixed_ips`.

The **multiple port bindings** feature (Stein;
[spec](https://specs.openstack.org/openstack/neutron-specs/specs/backlog/pike/portbinding_information_for_nova.html),
[internals](https://docs.openstack.org/neutron/latest/contributor/internals/live_migration.html))
is the part worth copying. A port may hold several `PortBinding` rows, one per
host, of which exactly one is `ACTIVE`. Nova creates an **inactive** binding on
the destination during `pre_live_migration`, uses it to build the destination
VIF, then activates it (which deactivates the source's) and deletes the source
binding afterwards. The spec's justification: *"We simply cannot move the port
binding to the pre_live_migration stage as the original port binding would be
deleted, causing issues due to the instance being active still on the original
host."* The point is that **binding the destination is an operation that can
fail, and it must be attempted and validated before the guest is committed to
the move.**

Where the IP cannot follow, Neutron says so structurally:

- **Provider (VLAN) networks** are usable only on hosts whose L2 agent has a
  matching `bridge_mappings` entry. Overlay (VXLAN/Geneve) networks are usable
  from any host with a tunnel endpoint.
- **Routed provider networks / segments**
  (<https://docs.openstack.org/neutron/latest/admin/config-routed-networks.html>)
  give one network several L2 segments, each with its own subnet, and state
  outright that "the particular IP addresses available to an instance depend on
  the segment of the network available on the particular compute node". The
  scheduler is required to place an instance on a host with access to a segment
  that has free addresses.
- **Floating IPs** are the indirection layer for everything the fixed IP cannot
  do.

### Ganeti, CloudStack

- **Ganeti** models networks cluster-wide (`gnt-network`) with the IP pool as
  two bitarrays: reservations, and *external* reservations for gateway and
  broadcast (Convoy's `state_reason = system` is the same idea). A NIC
  references a Network; `ip=pool` allocates from it. `gnt-instance migrate`
  (live, to the secondary node) and `failover` (stop, re-point primary, start)
  both move execution only; the NIC's MAC and IP are instance config and are
  never rewritten
  ([design-network](https://docs.ganeti.org/docs/ganeti/2.13/html/design-network.html)).
- **CloudStack**'s `migrateVirtualMachine` takes only
  `virtualmachineid`, `hostid`/`autoselect` and `storageid`, with no NIC or IP
  parameter at all
  ([API 4.18](https://cloudstack.apache.org/api/apidocs-4.18/apis/migrateVirtualMachine.html)),
  because networks are zone-scoped and migration never crosses a zone. Crossing
  that boundary is a different feature ("Portable IP").

### Virtualizor, SolusVM: the closest match to Convoy's shape

Both are node-scoped IP-pool panels, and both solve this with an explicit
user-facing toggle plus a hard prerequisite.

- **Virtualizor**: the migrate API takes `preserve_ip` (1/0),
  *"Set this option to use same IP Address(s) on the migrated VPS"*, and the
  docs say plainly that without it the VPS gets a new address from the
  destination. Live migration additionally requires that the *"IP pool on panel
  ... should be shared amongst servers"* and routed to both
  ([migrate-vps](https://www.virtualizor.com/docs/admin-api/migrate-vps/),
  [live migration](https://www.virtualizor.com/docs/admin/live-migration/)).
- **SolusVM 2**: migration preserves the IP by default, with the stated
  prerequisite *"The destination compute resource must have the same IP block
  that is assigned to the source compute resource"*, and a "Preserve IP"
  checkbox the operator can clear to re-IP instead
  ([docs](https://docs.solusvm.com/v2/administrator-guide/Migration+of+Servers+Between+Compute+Resources.html)).

Convoy's `address_block_group_to_network_interface` pivot is the same fact as
SolusVM's "IP block assigned to a compute resource". The prerequisite
translates directly: **the destination node must have an interface attached to
every group the server's addresses live in.**

### Public cloud UX

- **Linode**: cross-region migration changes the IP. The docs state IPs "are
  not transferrable"; the new IPv4/IPv6 are **reserved when the Linode enters
  the migration queue and shown on its Networking page before the move
  completes**, and Cloud Manager shows a caution message before the operator
  proceeds. In-region host migration keeps the IP
  ([migrate to a new data center](https://techdocs.akamai.com/cloud-computing/docs/migrate-to-a-new-data-center)).
- **DigitalOcean**: *"you cannot preserve a Droplet's IP address when
  transferring the Droplet between regions"*; Reserved IPs are reassignable
  only within a datacenter
  ([docs](https://docs.digitalocean.com/support/can-i-preserve-my-droplets-ipv4-address-when-i-transfer-the-droplet/)).
- **Vultr**: Reserved IPs attach to one instance at a time and only within a
  region ([docs](https://docs.vultr.com/products/network/reserved-ips)).

The transferable pattern: **compute the new addresses before the move, show
them, and require an explicit acknowledgement.**

### NetBox, phpIPAM: declared vs discovered

- **NetBox** has **no FK from `IPAddress` to `Prefix`**. Containment is
  computed at query time with Postgres `inet` operators, and a proposal to add
  a cached FK was closed as not planned
  ([#7845](https://github.com/netbox-community/netbox/issues/7845)). The
  consequence is the one Convoy needs: *an address can be recorded even though
  no managed prefix covers it.*
- `IPAddress.status` is the reconciliation lever: `active`, `reserved`,
  `deprecated`, `dhcp` ("Assigned dynamically via DHCP"), `slaac`. `dhcp` and
  `slaac` exist precisely to record "this is assigned somewhere else" without
  claiming to know the value. `deprecated` is where stale records go instead of
  being deleted. `assigned_object` (a generic FK to an interface) is nullable,
  so "recorded but attached to nothing" is a legal state.
- Duplicates are allowed by default: `ENFORCE_GLOBAL_UNIQUE` defaults to
  `False` in the shipped config, and per-VRF `enforce_unique` is off by default
  ([VRF docs](https://netbox.readthedocs.io/en/stable/models/ipam/vrf/)). A
  proposal to flip the global default is still open
  ([#14536](https://github.com/netbox-community/netbox/issues/14536)).
- `Prefix.status` (`container` / `active` / `reserved` / `deprecated`),
  `is_pool` and `mark_utilized` let an operator say "this is full, stop
  reconciling into it" independently of what the tool can see.
- **Import tooling** consistently tags what it created so later runs never
  overwrite hand-entered rows
  ([netbox-scanner](https://github.com/lopes/netbox-scanner)). NetBox Labs'
  current answer, **Diode**, calls its job "reconciliation logic ... to identify
  drift and deviations" and now **stages ingested changes onto a branch for a
  human to merge** rather than writing straight through
  ([NetBox Discovery](https://netboxlabs.com/docs/discovery/)). Nautobot SSoT
  is the same idea on `DiffSync` (189 stars).
- **phpIPAM** took the opposite structural choice. `ip_addresses.subnetId` is
  a real FK, and it gets dangling rows when a subnet is deleted
  ([#1526 "Orphan IP addresses"](https://github.com/phpipam/phpipam/issues/1526)).
  Its `discoveryCheck.php` enumerates a subnet's possible addresses, subtracts
  what is already in the database, pings the rest, and inserts every responder
  with `state = 2` (online) and the description `--autodiscovered--`. There is
  no review step; the write is immediate and irreversible. Subnets carry an
  `isFull` flag that refuses further additions.

Vocabulary worth borrowing: **drift** (NetBox Labs), **orphaned** for
modelled-but-not-observed (phpIPAM's own bug tracker), and
**desired state vs observed state** (the Kubernetes IPAM CRDs, and AWS VPC
IPAM's separate "Resource Discovery" object).

---

## 3. Migration: options and recommendation

### The decision

An address follows a server to a new node **iff its address block group is
reachable from that node**, that is, when the destination node owns a
`network_interface` appearing in `address_block_group_to_network_interface`
for that group. That condition is
already representable; nothing needs to be invented to ask it.

| Option | What it is | When it applies | Cost |
|---|---|---|---|
| **A. Keep the IP** | `node_id` and `network_interface_id` change; `addresses` rows are untouched. | Destination has an interface on every group the server's addresses live in, with a matching bridge name (PVE requires the name to match for intra-cluster migration) and a compatible VLAN tag. Physically true for a stretched VLAN, a shared L2 fabric, or an SDN VXLAN/EVPN zone. | None beyond the migration itself. Online migration is possible. |
| **B. Release and reallocate** | Release the current addresses (`assigned` → `available`), allocate on the destination interface, rewrite `ipconfigN` and the NIC, then stop/start. | Destination cannot reach the group. Physically true for per-rack /24s, per-node routed blocks, and anything resembling Neutron's routed provider networks. | The guest's IP changes. Requires a reboot for cloud-init to re-run, or a manual in-guest change. Not compatible with online migration. |
| **C. Block the migration** | Refuse with an actionable message. | Destination is in the same cluster and has a same-named bridge, but that bridge is not attached to the group, i.e. the topology model is incomplete rather than genuinely different. | The operator attaches the group and retries. |
| **D. Floating/reserved IP indirection** | A second address object that re-points. | Would decouple the tenant-visible address from placement entirely. | A whole new concept, needs NAT or host routes on every node. |

### Recommendation

**Implement A and B, offered as an explicit choice, with C as the default
refusal when the destination is ambiguous. Do not build D.**

Concretely:

1. **Preflight before the operator commits.** Call
   `GET /nodes/{node}/qemu/{vmid}/migrate` and compute, per candidate
   destination node:
   - PVE's own verdict (`allowed_nodes` / `not_allowed_nodes`, with
     `unavailable_storages`, `local_disks`, `local_resources`),
   - whether a bridge of the same name exists (the constraint PVE itself
     imposes, and the one `ServerPlacementService::rehome()` already uses),
   - whether every group holding one of the server's addresses is attached to
     an interface on that node,
   - the resulting **IP disposition**: `preserved`, `reallocated`, or
     `blocked`.
2. **Show the disposition, and for `reallocated` show the actual new
   addresses.** Allocate them speculatively under `FOR UPDATE SKIP LOCKED`
   inside the confirmation request, exactly the way Linode reserves and
   displays the destination IPs before the move. Do not make the operator
   discover the change afterwards.
3. **Require an acknowledgement for `reallocated`**, a checkbox rather than a
   tooltip. Copy the Virtualizor/SolusVM control, not their default: because
   Convoy's groups are node-scoped by construction, "preserve" is the case that
   needs proving, so the panel computes the disposition rather than letting the
   operator assert it.
4. **Order the writes the Neutron way.** Validate and reserve the destination
   binding (interface, VLAN tag, and for option B the new addresses) *before*
   issuing the PVE migrate task. Commit the panel-side rebind only when the
   task reports success; release the speculative allocation on failure. Never
   release the source addresses until the destination is confirmed.
5. **Reuse `ServerPlacementService`'s re-home for the commit**, extended to
   re-check address reachability and to flag rather than silently succeed when
   it fails. That closes the latent bug in §1 for out-of-band migrations too,
   which is the other half of #161.

### UI, before the commit

The destination picker is a list of nodes, each row carrying its verdict:

- *Keeps its IP addresses*: green, allows online migration when the guest is
  running.
- *Gets new IP addresses*: amber, lists the addresses it would get and the
  ones it would release, forces the acknowledgement, and states that the server
  is stopped and restarted.
- *Unavailable*: greyed, with the reason from PVE (`unavailable_storages`,
  `local_resources`) or from Convoy (`vmbr1 is not attached to the "Public
  /24" pool on this node`).

Per `AGENTS.md`, this is a `PageToolbar` action opening a dialog, and the
verdict rows are a `<dl>`-dense card, not one card per fact.

---

## 4. Import: reconciliation cases

Import adopts a guest that already exists on a registered node. The panel reads
`/cluster/resources` (already cached), subtracts guests it already owns by
`(cluster, vmid)`, and offers the remainder for adoption, the "adoption over
forms" pattern `docs/templates-handoff.md` already advocates, and the same
"observed, not typed" rule.

Address candidates come from `ipconfigN` (authoritative: it is what the panel
can write) with `netN` for the bridge, MAC and VLAN tag, and the guest agent's
`network-get-interfaces` used only as corroborating evidence. The guest agent
is the **only** API path to what the guest actually has; a guest without
`qemu-guest-agent` yields `ipconfigN` or nothing, which is why case 11 exists
and why the agent's view never drives allocation.

Containment is a Postgres `inet` test against `address_blocks`
(`ip << base_ip/prefix_length_from`), **scoped to the groups reachable from the
guest's node**. An unscoped containment test would match a block on a node the
guest cannot reach and is wrong.

| # | Observed state | Panel state | Verdict | Action |
|---|---|---|---|---|
| 1 | IP in a reachable block | Row exists, `available` | **Claim** | `server_id`, `state = assigned`, `origin = imported`, `observed_at = now()` |
| 2 | IP in a reachable **sparse** block | No row (never materialized) | **Mint and claim** | Insert the exact address; the `unique (address_block_id, ip)` index makes it safe. Needs a new "mint this specific address" path, since `mintFromSparseBlock()` only appends after `MAX(ip)` |
| 3 | IP in a reachable **dense** block | No row (block never generated) | **Mint and claim** | Same as 2. Do not run full generation as a side effect of an import |
| 4 | IP in a reachable block | Row exists, `assigned` to **this** server | **No-op** | Refresh `observed_at`. The re-import / re-scan case |
| 5 | IP in a reachable block | Row exists, `assigned` to **another** server | **Conflict** | Never steal. Import the guest with that address unclaimed, set `flagged_at` / `flag_reason` naming both servers, and list it in the import result |
| 6 | IP in a reachable block | Row exists, `reserved` + `system` (network / broadcast / gateway) | **Refuse and flag** | A guest is using an address the panel considers a hazard. Real operator problem; surface it, do not paper over it |
| 7 | IP in a reachable block | Row exists, `reserved` + `admin` | **Refuse and flag** | Held out of the pool deliberately. Offer "unreserve and claim" as a one-click follow-up |
| 8 | IP in a block that is **not** reachable from this node | Any | **Refuse and flag** | The topology model is incomplete or the guest is on an unmodelled bridge. Offer "attach this pool to `<bridge>` on `<node>`, then retry" |
| 9 | IP in **two or more** blocks | n/a | **Ambiguous, refuse** | Name the candidate blocks. Overlapping blocks across groups are legal today and nothing resolves the tie |
| 10 | IP outside every block | n/a | **Record as unmanaged** | Import the server with no panel-managed address for it; show the observed value on the row. Offer "create a block for this" as a separate action. Never auto-create a block (NetBox's precedent: an address may exist with no covering prefix) |
| 11 | `ipconfigN` is `dhcp` / `ip6=auto`, or absent with no agent | n/a | **Unknown** | Import with zero addresses and mark the server so `ServerNetworkService::syncSettings()` will not rewrite `ipconfigN`. This is #111's DHCP request, still a stub |
| 12 | Agent reports an in-guest IP ≠ `ipconfigN` | n/a | **Record drift** | Trust `ipconfigN` for allocation; record the agent's view and flag. Evidence, not authority |
| 13 | Guest VMID already held in this cluster | n/a | **Refuse** | `Server::isUniqueVmId()` already enforces this |
| 14 | Guest has `template: 1` | n/a | **Not adoptable** | Excluded from the list entirely; see `docs/templates-handoff.md` |

Two rules that hold across the table:

- **An import never writes to the guest.** No `smbios1` stamp, no `ipconfig`
  rewrite, no NIC rewrite, no firewall write, until the operator explicitly
  asks for it afterwards. The existing `should_create_vm: false` path violates
  this and must not be reused as-is.
- **A refusal imports the server anyway, flagged.** Refusing the whole guest
  because one of its four IPs is ambiguous is the phpIPAM-style all-or-nothing
  behaviour that makes operators stop using the feature. Import the row, leave
  the address unclaimed, flag, and let a human resolve it: Diode's
  stage-and-merge shape, at one-guest granularity.

---

## 5. Recommended data-model change

### What does not change, deliberately

**Do not add `node_id` or `network_interface_id` to `addresses`.** It is the
obvious change and it is the wrong one. Neutron's port/binding split is exactly
the lesson: the address belongs to its block (the segment), the *server*
belongs to a node and an interface, and a migration is a rebind of the server.
Putting the host on the address row would mean every migration rewrites every
address row, and a failed migration would leave them rewritten.

`Node::addresses()` stays derived. `address_block_group_to_network_interface`
stays the single source of truth for reachability.

### Added columns

| Change | Reasoning |
|---|---|
| `addresses.origin`: string, values `generated` / `allocated` / `imported`, default `generated` | Reconciliation needs to know whether a row is a claim about the world or a record of it. Every import tool in §2 keeps this distinction (netbox-scanner tags its own rows so scans never overwrite hand-entered ones; phpIPAM writes the literal `--autodiscovered--`). Without it, a re-scan cannot tell a row it created from one an operator typed. |
| `addresses.observed_at`: `timestamp`, nullable | Powers "modelled but not observed" (orphaned) and dates a conflict. Null means never seen in a guest config, which is the correct state for the entire pre-existing pool. |
| `AddressStateReason::Conflict`: new enum case | Case 5 needs a machine-readable resting state that is not "assigned" and not "free". `state = reserved, state_reason = conflict` reuses the existing full lock (`AddressAllocationService` already skips everything that is not `available`, and `UpdateAddressRequest` already refuses to assign a reserved address) with zero changes to the allocator. |
| `servers.ipconfig_managed`: boolean, default `true` | Case 11 and any adopted guest need a way to say "the panel does not own this guest's IP configuration". `ServerNetworkService::syncSettings()` reads it and skips `syncCloudinitIpConfig()`. Also the seam for #111's DHCP request. |

Optionally, and only if operators start attaching one group to interfaces in
two different clusters: `address_block_groups.cluster_id`, mirroring
`storages.cluster_id`. The pivot already answers every question migration and
import ask, so this is a guard against operator error, not a requirement.
Defer it.

### No new tables

- **Migration runs** reuse `deployments` + `deployment_steps`, which already
  carry `type`, `status`, `requested_at` / `completed_at` and a per-step
  `task_upid`. Add `DeploymentType::MIGRATE`. A dedicated `server_migrations`
  table would duplicate the progress machinery for one extra pair of columns
  (`from_node_id`, `to_node_id`) that the audit log already records;
  `AuditEvent::ADMIN_SERVER_REHOMED` logs `from`, `to` and `vmid` today.
- **Adoptable guests** are derived, not stored: `/cluster/resources` minus
  `servers` by `(cluster, vmid)`. The poll already fetches and caches it
  (`GuestStateCache`). A table would be a cache of a cache that goes stale
  whenever someone deletes a VM in the PVE UI.

### Code that moves

The reachability invariant moves out of
`App\Http\Requests\Admin\Addresses\UpdateAddressRequest` into a service, say
`App\Services\Addresses\AddressReachabilityService`, alongside
`AddressAvailabilityService`, with two methods:

```
isReachable(Address $address, Node $node): bool
blocksReachableFrom(Node $node): Collection<AddressBlock>
```

Four callers need it and only one has it today: the manual assignment request,
`ServerPlacementService::rehome()`, the migration preflight, and the import
containment scope. Leaving it in a request class is why an out-of-band
migration can violate it silently.

### Dependencies

**None.** Containment and geometry are already covered:

- Postgres `inet` does containment (`<<`), ordering and arithmetic in SQL; the
  columns are already `inet`.
- `mlocati/ip-lib` is already in `composer.json` (`^1.20`) and is used
  throughout `AddressBlock`. **315 stars, last push 2026-09-17, zero open
  issues, MIT, no runtime dependencies**. Reputable and actively maintained.

Vetted and rejected:

| Candidate | Stars | Verdict |
|---|---|---|
| `rlanvin/php-ip` | 180 | **Reject.** No commits since 2022-03; 13 open issues. Unmaintained. |
| `darsyn/ip` | 257 | **Reject.** Well maintained (last push 2026-09-16) and offers an immutable value object with a Doctrine type, but duplicates `ip-lib` and the Doctrine integration is useless in an Eloquent app. |
| `markrogoyski/ipv4-subnet-calculator-php` | 175 | **Reject.** IPv4 only; Convoy is dual-stack. |
| `networktocode/diffsync` | 189 | **N/A.** Python. Cited as the diff-and-sync design pattern only. |
| phpIPAM / NetBox | 2.8k / ~17k | **N/A.** Standalone applications, not libraries. Design prior art only; Convoy's IPAM is the authority and a second one underneath it is a merge problem, not a dependency. |

---

## 6. Scope

Full coverage is explicitly not the goal. The cut line:

### In scope

- **Intra-cluster migration** between two nodes Convoy has registered in the
  same `Cluster`, via `POST /nodes/{node}/qemu/{vmid}/migrate`. Online when the
  guest is running and the IP is preserved; offline otherwise.
- **Preflight from `GET .../migrate`** plus Convoy's own reachability verdict,
  rendered per candidate node before the operator commits.
- **Two IP dispositions**: preserve, or release-and-reallocate with the
  destination addresses shown in advance and an explicit acknowledgement.
- **Adoption of one guest at a time** from the unclaimed list on a registered
  node, with per-address reconciliation as in §4 and no writes to the guest.
- **Extending `ServerPlacementService`** to re-check reachability on every
  re-home, closing the silent-unroutability bug for out-of-band migrations.
- **QEMU guests only.**
- **`net0` / `ipconfig0` only** for address reconciliation; additional NICs are
  read, reported, and left unclaimed.

### Out of scope, deliberately

- **Cross-cluster migration** (`remote_migrate`). It is the only endpoint with
  bridge remapping, but it also needs a remote-endpoint store, an API token per
  remote, `target-vmid` coordination and a `delete`-the-source decision. Its
  own feature.
- **Proxmox SDN.** No zone/VNet/subnet management, no PVE IPAM backend, no SDN
  DHCP. Convoy's IPAM is the authority; adopting PVE's would be a merge. SDN is
  relevant here only as the reason an operator can truthfully attach one group
  to interfaces on every node.
- **DHCP and SLAAC guests.** `ipconfig_managed = false` records that the panel
  does not own the address; nothing allocates or tracks a lease. #111's
  original request stays open.
- **MTU handling** (also #111). The `mtu` sub-key already round-trips through
  `NetworkDeviceData::extraProperties`; nothing in this design reads or writes
  it.
- **Bulk operations**: `/nodes/{node}/migrateall`,
  `/cluster/bulk-action/guest/migrate`, node evacuation/drain, bulk adopt.
- **HA-managed guests.** `/cluster/ha/resources/{sid}/migrate` is a different
  endpoint with different semantics; `GET .../migrate` already reports
  `blocking-ha-resources` and those nodes are simply marked unavailable.
- **In-guest reconfiguration.** Reallocation rewrites `ipconfigN` and requires
  a reboot; no agent-driven `ip addr` change, and no attempt at zero-downtime
  re-IP.
- **LXC containers.** `/nodes/{node}/lxc/{vmid}/migrate` exists and is not
  wired up.
- **Importing from unregistered nodes or foreign clusters**, importing disk
  images, or reconstructing an image/template lineage for an adopted guest
  (`image_definition_id` stays null).
- **Automatic block creation** from observed addresses. Case 10 offers it; it
  never happens on its own.
- **Reverse DNS / PTR**, and floating or reserved IPs as a concept.
- **Fixing `mac_address` living on `AddressBlock` rather than `Address`.** It
  is a pre-existing oddity that makes every address in a block share one MAC;
  it interacts with import (an adopted guest has its own MAC) but fixing it is
  its own change.
