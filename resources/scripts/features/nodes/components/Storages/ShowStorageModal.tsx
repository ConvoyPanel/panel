import byteSize from 'byte-size'
import { useMemo } from 'react'
import { useShallow } from 'zustand/react/shallow'

import useStoragesModalStore from '@/features/nodes/hooks/use-storages-modal-store.ts'

import {
    Credenza,
    CredenzaBody,
    CredenzaContent,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'
import { SegmentedProgressBar, type Segment } from '@/components/ui/Progress'

const fmt = (bytes: number) => {
    const { value, unit } = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${value} ${unit}`
}

const ShowStorageModal = () => {
    const [storage, open, close] = useStoragesModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'show',
            state.closeModal,
        ])
    )

    // Prefer live Proxmox capacity (the truth — includes the base system and any
    // non-Convoy consumers). Fall back to Convoy's own allocation against the
    // configured size only when the node is offline.
    const view = useMemo(() => {
        if (!storage) return null

        const committed =
            storage.usages.server + storage.usages.backup + storage.usages.iso

        if (storage.online && storage.physicalTotal) {
            const total = storage.physicalTotal
            const used = storage.physicalUsed ?? 0
            const untracked = storage.untracked ?? 0
            const free = storage.physicalFree ?? 0
            const freeForConvoy = storage.freeForConvoy ?? free
            // Reserve is carved out of the free space, not from what's used.
            const reservedShown = Math.max(0, free - freeForConvoy)

            const segments: Segment[] = [
                { label: 'KVM', value: pct(storage.usages.server, total), color: 'hsl(var(--chart-1))' },
                { label: 'Backups', value: pct(storage.usages.backup, total), color: 'hsl(var(--chart-2))' },
                { label: 'ISO Images', value: pct(storage.usages.iso, total), color: 'hsl(var(--chart-3))' },
                { label: 'Untracked (base system + other)', value: pct(untracked, total), color: 'hsl(var(--chart-4))' },
                { label: 'Reserved (headroom)', value: pct(reservedShown, total), color: 'hsl(var(--muted-foreground) / 0.35)' },
            ]

            return {
                online: true as const,
                total,
                used,
                freeForConvoy,
                reserved: reservedShown,
                segments,
            }
        }

        // Offline fallback: Convoy's bookkeeping against the configured size.
        const total = storage.size
        const segments: Segment[] = [
            { label: 'KVM', value: pct(storage.usages.server, total), color: 'hsl(var(--chart-1))' },
            { label: 'Backups', value: pct(storage.usages.backup, total), color: 'hsl(var(--chart-2))' },
            { label: 'ISO Images', value: pct(storage.usages.iso, total), color: 'hsl(var(--chart-3))' },
        ]

        return {
            online: false as const,
            total,
            used: committed,
            freeForConvoy: Math.max(0, total - committed),
            reserved: 0,
            segments,
        }
    }, [storage])

    if (!view) return null

    const usedPercent = view.total ? (view.used / view.total) * 100 : 0

    return (
        <Credenza open={open} onOpenChange={open => !open && close('show')}>
            <CredenzaContent className={'gap-0'}>
                <CredenzaHeader>
                    <CredenzaTitle>
                        {storage?.displayName ?? storage?.name}
                    </CredenzaTitle>
                </CredenzaHeader>
                <CredenzaBody>
                    {!view.online && (
                        <p
                            className={
                                'mb-2 rounded-md bg-muted px-3 py-2 text-sm text-muted-foreground'
                            }
                        >
                            Live usage unavailable — the node is offline. Showing
                            Convoy&rsquo;s own allocation against the configured
                            size.
                        </p>
                    )}
                    <p
                        className={
                            'mb-1 text-right text-sm text-muted-foreground'
                        }
                    >
                        {fmt(view.used)} used out of {fmt(view.total)} &#x2022;{' '}
                        {usedPercent.toFixed(2)}%
                    </p>
                    <SegmentedProgressBar
                        className={'h-4'}
                        segments={view.segments}
                    />
                    <ul
                        className={'mt-2 flex flex-wrap gap-3'}
                        aria-hidden={true}
                    >
                        {view.segments
                            .filter(segment => segment.value > 0)
                            .map(segment => (
                                <li
                                    key={segment.label}
                                    className={'flex items-center text-sm'}
                                >
                                    <span
                                        className={'mr-1 size-2 rounded-full'}
                                        style={{ backgroundColor: segment.color }}
                                    />
                                    {segment.label}
                                </li>
                            ))}
                    </ul>

                    {view.online && (
                        <dl
                            className={
                                'mt-6 grid grid-cols-2 gap-x-6 gap-y-2 text-sm'
                            }
                        >
                            <dt className={'text-muted-foreground'}>
                                Free for Convoy
                            </dt>
                            <dd className={'text-right font-medium'}>
                                {fmt(view.freeForConvoy)}
                            </dd>
                            {view.reserved > 0 && (
                                <>
                                    <dt className={'text-muted-foreground'}>
                                        Reserved headroom
                                    </dt>
                                    <dd className={'text-right font-medium'}>
                                        {fmt(view.reserved)}
                                    </dd>
                                </>
                            )}
                        </dl>
                    )}
                </CredenzaBody>
            </CredenzaContent>
        </Credenza>
    )
}

const pct = (part: number, total: number) =>
    total > 0 ? (part / total) * 100 : 0

export default ShowStorageModal
