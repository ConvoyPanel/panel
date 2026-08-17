import useStoragesModalStore from '@/features/nodes/hooks/use-storages-modal-store.ts'
import { storageCapacity } from '@/features/nodes/storages/capacity.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import byteSize from 'byte-size'
import { useMemo } from 'react'

import { SegmentedProgressBar } from '@/components/ui/Progress'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogContent,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'

const fmt = (bytes: number) => {
    const { value, unit } = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${value} ${unit}`
}

const ShowStorageModal = () => {
    const {
        open,
        data: storage,
        close,
    } = useModal(useStoragesModalStore, 'show')

    // One derivation, shared with the list row, so the modal and the row can
    // never disagree about the same storage.
    const view = useMemo(
        () => (storage ? storageCapacity(storage) : null),
        [storage]
    )

    if (!view) return null

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent className={'gap-0'}>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {storage?.displayName ?? storage?.name}
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody>
                    {view.source !== 'live' && (
                        <p
                            className={
                                'bg-muted text-muted-foreground mb-2 rounded-md px-3 py-2 text-sm'
                            }
                        >
                            {view.source === 'recorded'
                                ? 'The node is unreachable. These are the figures Convoy last recorded from it.'
                                : 'Convoy has never reached this storage, so its capacity is unknown.'}
                        </p>
                    )}
                    <p
                        className={
                            'text-muted-foreground mb-1 text-right text-sm'
                        }
                    >
                        {fmt(view.used)} used out of {fmt(view.total)} &#x2022;{' '}
                        {view.percent.toFixed(2)}%
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
                                        style={{
                                            backgroundColor: segment.color,
                                        }}
                                    />
                                    {segment.label}
                                </li>
                            ))}
                    </ul>

                    {view.known && (
                        <dl
                            className={
                                'mt-6 grid grid-cols-2 gap-x-6 gap-y-2 text-sm'
                            }
                        >
                            <dt className={'text-muted-foreground'}>
                                Free for Convoy
                            </dt>
                            <dd className={'text-right font-medium'}>
                                {fmt(view.freeForConvoy ?? 0)}
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
                </ResponsiveDialogBody>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default ShowStorageModal
