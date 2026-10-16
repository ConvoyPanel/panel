# Server migration over Anchor

The wire contract between the panel and the Anchor daemons on the source and
destination nodes. Both repos build against this file; it is the thing that must
not drift.

## Why not the two obvious alternatives

`qm migrate` is the only supported live migration and it is intra-cluster only.
Clustering two standing nodes is not available to us: `assert_joinable` refuses a
node that holds any guest, joining replaces `/etc/pve`, a removed node has to be
reinstalled rather than rejoined, and a two-member cluster drops `/etc/pve` to
read-only on the survivor whenever its peer is down.

`qm remote-migrate` needs no cluster, but Proxmox marks it `EXPERIMENTAL feature!`
in both `PVE/API2/Qemu.pm` and `PVE/CLI/qm.pm`, and it appears nowhere in the
shipped admin guide.

`vzdump` and `qmrestore` are stable, documented, and already how Anchor installs
an image. The cost is that this is **offline migration**: the guest is stopped for
the whole transfer. Nothing here attempts live migration, and nothing should.

## Shape

The destination side is the install pipeline that already exists
(`anchor/src/templates/install.rs`): fetch a URL, verify a sha256, `qmrestore`
into a checked VMID. Migration adds only a *source* side that produces such a URL.

```
panel ──Export{vmid,mode}──▶ source anchor          vzdump -> artifact + sha256
panel ◀──Status{job}────────  source anchor          {artifact, sha256, size}
panel ──Install{url,sha256}─▶ destination anchor     existing pipeline, unchanged
panel ◀──Status{job}────────  destination anchor
panel ──Discard{artifact}───▶ source anchor          only after the destination is verified
```

The destination pulls **directly from the source node**, never through the panel.
Both nodes are on the same tailnet and the panel has no business carrying
multi-gigabyte bodies.

## New protocol members

Implemented in `anchor/src/protocol/mod.rs`. The shipped enum is `TemplateAction`,
not `Command`; the variants below were added to it.

```rust
pub enum TemplateAction {
    Install(Box<InstallSpec>),
    Export(Box<ExportSpec>),          // new
    Fetch { artifact: String },       // new -- authorises ONE download
    Discard { artifact: String },     // new
    Status { job: String },
    Cancel { job: String },
}

pub struct ExportSpec {
    pub vmid: u32,
    /// `stop` is the only mode a migration may use: a snapshot-mode archive is
    /// crash-consistent as of when it started, so every write the guest makes
    /// during the transfer would be lost at cutover without anyone being told.
    pub mode: ExportMode,       // serde default: stop
    pub compress: Compression,  // serde default: zstd
    pub storage: Option<String>,
}
```

`Status` on an export job returns, on success:

```json
{ "artifact": "<opaque id>", "sha256": "<hex>", "size": 12345678,
  "path": "/var/lib/vz/dump/vzdump-qemu-…vma.zst" }
```

An export job reports a new `JobStatus` variant, **`dumping`**, while `vzdump`
runs. Any consumer parsing job status must accept it.

Export refuses a guest that is not in that node's own `qemu-server` directory, so
the export command must be sent to the node actually holding the guest.

## Serving the artifact

The source Anchor serves `GET /artifacts/{artifact}` — at the root, not under
`/api/v1`. Export job status and cancellation are at
`/api/v1/templates/jobs/{id}`, which is a different route from the installs path.

Authorisation reuses `TemplateClaims` with the new `Fetch { artifact }` action
rather than a third claims type. A token names one artifact and one action: a
`discard` token presented for a download is a 401.

- Single `Range` requests are supported (open-ended, closed and suffix), with
  `416` and `bytes */size` when unsatisfiable, and `ETag` set to the sha256. The
  install side retries a dropped download three times and that is worthless
  without resumption.
- A **multi-range** request returns `200` with the whole body, not `206`. Do not
  depend on multi-range.
- The endpoint streams in 64 KiB chunks and never reads the archive into memory.
- Artifacts expire after `export_ttl_secs` (default 24h) **and** are cleared when
  Anchor restarts. A migration that outlives either loses its artifact, so
  "artifact gone" is a recoverable re-export, not a hard failure.

Config added: `vzdump_path`, `exports_enabled`, `export_dir`, `export_ttl_secs`.
Capability: `migration.export`.

## Ordering, and what makes it safe

The source guest is **not** destroyed until the destination guest is verified to
exist and be configured. This ordering is the whole robustness story:

1. Panel computes the address disposition (existing `ServerMigrationService`
   logic, unchanged) and refuses or warns before anything is touched.
2. Panel reserves the destination binding. Existing behaviour.
3. `qm stop` the source guest. Downtime starts.
4. `Export` on the source. Artifact hashed.
5. `Install` on the destination, VMID allocated there (Anchor's `vmid::taken`
   already reads the cluster-wide vmlist with a per-node fallback). A collision
   is impossible by construction, which is what makes this work between nodes
   that both run a guest 100.
6. Verify the destination guest: config present, disks attached, sizes match.
7. Only now: destroy the source guest, release the source addresses, `Discard`
   the artifact. Downtime ends when the destination guest starts.

Any failure before step 7 rolls back by restarting the source guest and freeing
the destination reservation. The source is untouched the entire time, so rollback
is always available and never involves restoring anything.

## What this does not do

- No live migration. The guest is down for the transfer.
- No delta or incremental transfer. A retry re-sends the archive.
- No LXC. QEMU only, matching the rest of the migration work.
- No cross-panel migration. Both nodes are enrolled with the same panel.
