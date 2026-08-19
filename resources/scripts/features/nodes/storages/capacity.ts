import { Storage } from '@/features/nodes/types.ts'

import type { Segment } from '@/components/ui/Progress'

/**
 * One reading of a storage's capacity, shared by the list row and the detail
 * modal so the two cannot disagree about the same storage.
 *
 * The figures come from the API, which already resolves live → recorded →
 * unknown and withholds `untracked` where the subtraction is not valid. Nothing
 * here recomputes that; this only decides how to draw it.
 */
export interface StorageCapacityView {
    /** Whether there are physical figures to show at all. */
    known: boolean
    source: Storage['capacitySource']
    total: number
    used: number
    percent: number
    /** What a new disk may consume: physical free − reserve. */
    freeForConvoy: number | null
    /** Carved out of free space, so drawn at the end of the used run. */
    reserved: number
    /** Convoy's ledger: server disks + backups + ISOs, as provisioned. */
    committed: number
    /**
     * Space on disk Convoy cannot account for. Null on thin and deduplicating
     * backends, where the ledger legitimately exceeds written bytes.
     */
    untracked: number | null
    isThin: boolean
    segments: Segment[]
}

const RESERVE_COLOR =
    'color-mix(in oklab, var(--muted-foreground) 35%, transparent)'

const pct = (part: number, total: number) =>
    total > 0 ? (part / total) * 100 : 0

/**
 * The fill for a store whose used space cannot be broken down.
 *
 * A backup-only store reads better in the backups colour than the VM-disk one --
 * it is the same quantity the modal would colour that way if it could split it.
 */
const solidColor = (storage: Storage) =>
    storage.storesBackups && !storage.storesKvm
        ? 'var(--chart-2)'
        : 'var(--chart-1)'

export const storageCapacity = (storage: Storage): StorageCapacityView => {
    const committed =
        storage.usages.server + storage.usages.backup + storage.usages.iso

    const total = storage.physicalTotal ?? 0
    const used = storage.physicalUsed ?? 0
    const free = storage.physicalFree ?? 0
    const freeForConvoy = storage.freeForConvoy
    // The reserve is the part of free space Convoy will not touch. Derived
    // rather than read so it always agrees with the two numbers around it.
    const reserved =
        freeForConvoy !== null ? Math.max(0, free - freeForConvoy) : 0

    const base = {
        source: storage.capacitySource,
        total,
        used,
        percent: pct(used, total),
        freeForConvoy,
        reserved,
        committed,
        untracked: storage.untracked,
        isThin: storage.isThin,
    }

    if (storage.capacitySource === 'unknown' || total <= 0) {
        return { ...base, known: false, segments: [] }
    }

    /*
     * Thin and deduplicating backends get one block rather than a breakdown.
     * Their per-consumer figures are provisioned sizes, so laying them across a
     * bar scaled to physical bytes would overflow it -- and on a PBS datastore
     * the two quantities are not even the same kind of thing.
     */
    if (storage.isThin) {
        return {
            ...base,
            known: true,
            segments: [
                {
                    label: 'Used',
                    value: pct(used, total),
                    color: solidColor(storage),
                },
                {
                    label: 'Reserved (headroom)',
                    value: pct(reserved, total),
                    color: RESERVE_COLOR,
                },
            ],
        }
    }

    return {
        ...base,
        known: true,
        segments: [
            {
                label: 'Server disks',
                value: pct(storage.usages.server, total),
                color: 'var(--chart-1)',
            },
            {
                label: 'Backups',
                value: pct(storage.usages.backup, total),
                color: 'var(--chart-2)',
            },
            {
                label: 'ISO images',
                value: pct(storage.usages.iso, total),
                color: 'var(--chart-3)',
            },
            {
                label: 'Untracked',
                value: pct(storage.untracked ?? 0, total),
                color: 'var(--chart-4)',
            },
            {
                label: 'Reserved (headroom)',
                value: pct(reserved, total),
                color: RESERVE_COLOR,
            },
        ],
    }
}

/**
 * Proxmox backend ids, under the names Proxmox itself uses for them.
 *
 * Deliberately not translated into plain English -- "Local folder" for `dir`
 * reads well right up until someone goes looking for it in the Proxmox UI and
 * finds nothing by that name. The label matches what the host calls it; the
 * explanation of what that means lives in a tooltip beside it.
 *
 * Unknown backends fall through to the raw id. An install can carry a plugin
 * Convoy has never heard of, and `foo` is more use than nothing.
 */
const BACKEND_LABELS: Record<string, string> = {
    dir: 'Directory',
    btrfs: 'Btrfs',
    lvm: 'LVM',
    lvmthin: 'LVM-thin',
    zfspool: 'ZFS',
    zfs: 'ZFS over iSCSI',
    nfs: 'NFS',
    cifs: 'SMB/CIFS',
    glusterfs: 'GlusterFS',
    iscsi: 'iSCSI',
    iscsidirect: 'iSCSI (direct)',
    cephfs: 'CephFS',
    rbd: 'Ceph RBD',
    pbs: 'Proxmox Backup Server',
    esxi: 'ESXi',
}

/**
 * What each backend actually is, for the tooltip.
 *
 * Written to answer "where does this live and what does it mean for me" rather
 * than to define the technology: whether the storage is on this host or on the
 * network is the part that changes what an operator does next.
 */
const BACKEND_HINTS: Record<string, string> = {
    dir: 'Files in a folder on this host’s own filesystem.',
    btrfs: 'A Btrfs filesystem on this host’s own disks.',
    lvm: 'An LVM volume group on this host’s own disks.',
    lvmthin:
        'An LVM thin pool on this host’s own disks. Space is allocated as guests write, so more can be promised than is used.',
    zfspool: 'A ZFS pool on this host’s own disks. Allocates on write.',
    zfs: 'ZFS volumes exported from another machine over iSCSI.',
    nfs: 'A directory shared over NFS from another machine. Every node that mounts it sees the same content.',
    cifs: 'A share mounted over SMB/CIFS from another machine.',
    glusterfs: 'A GlusterFS volume, shared across the cluster.',
    iscsi: 'Raw block devices from an iSCSI target.',
    iscsidirect: 'Raw block devices from an iSCSI target, addressed directly.',
    cephfs: 'A CephFS filesystem, reachable from every node in the cluster.',
    rbd: 'A Ceph block-storage pool, reachable from every node in the cluster.',
    pbs: 'A Proxmox Backup Server. Holds backups only, and deduplicates them on the server — so what it stores is far less than the backups add up to.',
    esxi: 'An ESXi datastore, used for importing existing virtual machines.',
}

export const backendHint = (type: string | null): string | null =>
    type === null ? null : (BACKEND_HINTS[type] ?? null)

export const backendLabel = (type: string | null): string | null =>
    type === null ? null : (BACKEND_LABELS[type] ?? type)
