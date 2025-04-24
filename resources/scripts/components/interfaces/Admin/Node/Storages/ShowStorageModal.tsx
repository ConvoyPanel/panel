import { useStoragesModalStore } from '@/routes/_app/admin/nodes.$nodeId/storages.lazy.tsx'
import byteSize from 'byte-size'
import { useShallow } from 'zustand/react/shallow'

import {
    Credenza,
    CredenzaBody,
    CredenzaContent,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'
import { SegmentedProgressBar } from '@/components/ui/Progress'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/Tabs'

const ShowStorageModal = () => {
    const [storage, open, close] = useStoragesModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'show',
            state.closeModal,
        ])
    )

    const used = byteSize(4000, {
        units: 'iec',
        precision: 2,
    })
    const total = byteSize(storage?.size ?? 0, {
        units: 'iec',
        precision: 2,
    })

    const usedPercent = storage ? (4000 / storage.size) * 100 : 0

    return (
        <Credenza open={open} onOpenChange={open => !open && close('show')}>
            <CredenzaContent className={'gap-0'}>
                <CredenzaHeader>
                    <CredenzaTitle>
                        {storage?.displayName ?? storage?.name}
                    </CredenzaTitle>
                </CredenzaHeader>
                <CredenzaBody>
                    <p
                        className={
                            'mb-1 text-right text-sm text-muted-foreground'
                        }
                    >
                        {used.value} {used.unit} used out of {total.value}{' '}
                        {total.unit} &#x2022; {usedPercent.toFixed(2)}%
                    </p>
                    <SegmentedProgressBar
                        className={'h-4'}
                        segments={[
                            {
                                label: 'KVM',
                                value: 24,
                                color: 'hsl(var(--chart-1))',
                            },
                            {
                                label: 'Backups',
                                value: 12,
                                color: 'hsl(var(--chart-2))',
                            },
                            {
                                label: 'Snapshots',
                                value: 14,
                                color: 'hsl(var(--chart-3))',
                            },
                            {
                                label: 'ISO Images',
                                value: 15,
                                color: 'hsl(var(--chart-4))',
                            },
                        ]}
                    />
                    <ul
                        className={'mt-2 flex flex-wrap gap-3'}
                        aria-hidden={true}
                    >
                        <li className={'flex items-center text-sm'}>
                            <span
                                className={
                                    'mr-1 size-2 rounded-full bg-[hsl(var(--chart-1))]'
                                }
                            />
                            KVM
                        </li>
                        <li className={'flex items-center text-sm'}>
                            <span
                                className={
                                    'mr-1 size-2 rounded-full bg-[hsl(var(--chart-2))]'
                                }
                            />
                            Backups
                        </li>
                        <li className={'flex items-center text-sm'}>
                            <span
                                className={
                                    'mr-1 size-2 rounded-full bg-[hsl(var(--chart-3))]'
                                }
                            />
                            Snapshots
                        </li>
                        <li className={'flex items-center text-sm'}>
                            <span
                                className={
                                    'mr-1 size-2 rounded-full bg-[hsl(var(--chart-4))]'
                                }
                            />
                            ISO Images
                        </li>
                    </ul>

                    <Tabs defaultValue={'all'} className={'mt-6 w-full'}>
                        <TabsList>
                            <TabsTrigger value={'all'}>All</TabsTrigger>
                            <TabsTrigger value={'kvm'}>KVM</TabsTrigger>
                            <TabsTrigger value={'backups'}>Backups</TabsTrigger>
                            <TabsTrigger value={'snapshots'}>
                                Snapshots
                            </TabsTrigger>
                            <TabsTrigger value={'iso'}>ISO Images</TabsTrigger>
                        </TabsList>
                    </Tabs>
                </CredenzaBody>
            </CredenzaContent>
        </Credenza>
    )
}

export default ShowStorageModal
