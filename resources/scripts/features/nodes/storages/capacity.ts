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
 * The line under a storage's name: what it is, not how full it is.
 *
 * Mirrors `anchorSummary` -- the facts that never change sit here so the
 * capacity column carries only the ones that do.
 */
export const storageSummary = (storage: Storage): string =>
    [
        storage.name,
        storage.pveType,
        storage.isThin
            ? storage.pveType === 'pbs'
                ? 'deduplicating'
                : 'thin'
            : null,
        storage.pveShared ? 'shared' : null,
    ]
        .filter(Boolean)
        .join(' · ')
