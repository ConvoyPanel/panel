import DeleteServerDialog from '@/features/nodes/components/Storages/DeleteServerDialog.tsx'
import StorageConsumerTable from '@/features/nodes/components/Storages/StorageConsumerTable.tsx'
import StorageSummary from '@/features/nodes/components/Storages/StorageSummary.tsx'
import {
    type StorageConsumer,
    deleteBackup,
    deleteIso,
    deleteServer,
    storageConsumersQuery,
    storageInventoryQuery,
} from '@/features/nodes/storages/api.ts'
import { storageCapacity } from '@/features/nodes/storages/capacity.ts'
import { cn } from '@/utils'
import { getApiErrorMessage } from '@/utils/http.ts'
import { IconDatabase, IconFileZip, IconServer } from '@tabler/icons-react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import byteSize from 'byte-size'
import { useState } from 'react'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { buttonVariants } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { SegmentedProgressBar } from '@/components/ui/Progress'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/Tabs'
import { toast } from '@/components/ui/Toast'
import { Heading, StatLabel } from '@/components/ui/Typography'

const fmt = (bytes: number) => {
    const { value, unit } = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${value} ${unit}`
}

/**
 * One storage, and what is filling it.
 *
 * A page rather than the modal this replaces: three tables of things an operator
 * may delete do not fit in a popover, and a confirmation inside an overlay is a
 * dialog within a dialog. It is also linkable, which "here is what filled the
 * disk" wants to be.
 */
const StorageDetail = ({ storageId }: { storageId: number }) => {
    const queryClient = useQueryClient()
    const confirm = useConfirmationStore(state => state.confirm)
    const [pendingServer, setPendingServer] = useState<StorageConsumer | null>(
        null
    )
    const [deleting, setDeleting] = useState(false)

    // The inventory is small and already cached by the list this page is
    // reached from, so it doubles as the lookup rather than adding an endpoint
    // that returns one row.
    const { data: storages } = useQuery(storageInventoryQuery)
    const storage = storages?.find(row => row.id === storageId)

    const { data: consumers, isLoading } = useQuery(
        storageConsumersQuery(storageId)
    )

    const refresh = () =>
        queryClient.invalidateQueries({
            queryKey: ['admin', 'storages'],
        })

    const run = async (action: () => Promise<unknown>, done: string) => {
        setDeleting(true)

        try {
            await action()
            await refresh()
            toast.add({ title: done, type: 'success' })
        } catch (error) {
            toast.add({
                title: getApiErrorMessage(error, 'Could not delete that'),
                type: 'error',
            })
        } finally {
            setDeleting(false)
        }
    }

    const view = storage ? storageCapacity(storage) : null

    return (
        <>
            <div className={'space-y-1'}>
                <Heading>
                    {storage?.displayName ?? storage?.name ?? 'Storage'}
                </Heading>
                {storage && <StorageSummary storage={storage} />}
            </div>

            <Card className={'overflow-hidden'}>
                <div className={'flex flex-col gap-3 p-4'}>
                    {view?.known ? (
                        <>
                            <div
                                className={
                                    'flex flex-wrap items-baseline justify-between gap-2'
                                }
                            >
                                <span
                                    className={'font-mono text-sm tabular-nums'}
                                >
                                    <strong>
                                        {fmt(view.freeForConvoy ?? 0)}
                                    </strong>{' '}
                                    available to allocate
                                </span>
                                <StatLabel className={'text-xs'}>
                                    {fmt(view.used)} used · {fmt(view.reserved)}{' '}
                                    reserved · {fmt(view.total)} total
                                </StatLabel>
                            </div>
                            <SegmentedProgressBar
                                className={'h-3'}
                                segments={view.segments}
                            />
                            <div
                                className={
                                    'flex flex-wrap gap-x-4 gap-y-1 text-xs'
                                }
                            >
                                {view.segments
                                    .filter(segment => segment.value > 0)
                                    .map(segment => (
                                        <span
                                            key={segment.label}
                                            className={
                                                'text-muted-foreground flex items-center gap-1.5'
                                            }
                                        >
                                            <i
                                                className={
                                                    'size-2 rounded-[2px]'
                                                }
                                                style={{
                                                    backgroundColor:
                                                        segment.color,
                                                }}
                                            />
                                            {segment.label}
                                        </span>
                                    ))}
                            </div>
                        </>
                    ) : (
                        <StatLabel className={'text-xs'}>
                            Convoy has not recorded capacity for this storage.
                        </StatLabel>
                    )}
                </div>

                {isLoading ? (
                    <div className={'flex flex-col gap-2 p-4'}>
                        {Array.from({ length: 3 }, (_, index) => (
                            <Skeleton key={index} className={'h-10'} />
                        ))}
                    </div>
                ) : (
                    <Tabs defaultValue={'servers'}>
                        <TabsList className={'mx-4'}>
                            <TabsTrigger value={'servers'}>
                                Servers {consumers?.servers.length ?? 0}
                            </TabsTrigger>
                            <TabsTrigger value={'backups'}>
                                Backups {consumers?.backups.length ?? 0}
                            </TabsTrigger>
                            <TabsTrigger value={'isos'}>
                                ISOs {consumers?.isos.length ?? 0}
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value={'servers'}>
                            <StorageConsumerTable
                                rows={consumers?.servers ?? []}
                                label={'Server'}
                                ownerLabel={'Owner'}
                                emptyIcon={IconServer}
                                emptyTitle={'No servers here'}
                                emptyDescription={
                                    'No server keeps a disk on this storage.'
                                }
                                onDelete={setPendingServer}
                            />
                        </TabsContent>

                        <TabsContent value={'backups'}>
                            <StorageConsumerTable
                                rows={consumers?.backups ?? []}
                                label={'Backup'}
                                ownerLabel={'Server'}
                                emptyIcon={IconFileZip}
                                emptyTitle={'No backups here'}
                                emptyDescription={
                                    'Nothing has been backed up to this storage.'
                                }
                                onDelete={async row => {
                                    const ok = await confirm({
                                        title: `Delete ${row.name}?`,
                                        description:
                                            'The backup is removed from Proxmox. Restoring from it will no longer be possible.',
                                        confirmText: 'Delete backup',
                                        confirmButton: {
                                            variant: 'destructive',
                                        },
                                    })

                                    if (ok) {
                                        void run(
                                            () => deleteBackup(row.routeKey),
                                            'Backup deleted'
                                        )
                                    }
                                }}
                            />
                        </TabsContent>

                        <TabsContent value={'isos'}>
                            <StorageConsumerTable
                                rows={consumers?.isos ?? []}
                                label={'ISO'}
                                emptyIcon={IconDatabase}
                                emptyTitle={'No ISOs here'}
                                emptyDescription={
                                    'No installation media is stored here.'
                                }
                                onDelete={async row => {
                                    const ok = await confirm({
                                        title: `Delete ${row.name}?`,
                                        description:
                                            'The file is removed from Proxmox. It can be downloaded again later.',
                                        confirmText: 'Delete ISO',
                                        confirmButton: {
                                            variant: 'destructive',
                                        },
                                    })

                                    if (ok && row.nodeId) {
                                        void run(
                                            () =>
                                                deleteIso(
                                                    row.nodeId as number,
                                                    row.routeKey
                                                ),
                                            'ISO deleted'
                                        )
                                    }
                                }}
                            />
                        </TabsContent>
                    </Tabs>
                )}
            </Card>

            {storage && storage.sharedWith.length > 0 && (
                <StatLabel className={'text-xs'}>
                    Reached from{' '}
                    {storage.sharedWith.map((node, index) => (
                        <span key={node.id}>
                            <Link
                                className={cn(
                                    buttonVariants({ variant: 'link' }),
                                    'h-auto p-0 text-xs font-normal'
                                )}
                                to={'/admin/nodes/$nodeId/storages'}
                                params={{ nodeId: String(node.id) }}
                            >
                                {node.name}
                            </Link>
                            {index < storage.sharedWith.length - 1 && ', '}
                        </span>
                    ))}
                </StatLabel>
            )}

            <DeleteServerDialog
                server={pendingServer}
                onOpenChange={() => setPendingServer(null)}
                isPending={deleting}
                onConfirm={() => {
                    const server = pendingServer

                    setPendingServer(null)

                    if (server) {
                        void run(
                            () => deleteServer(server.routeKey),
                            'Server deleted'
                        )
                    }
                }}
            />
        </>
    )
}

export default StorageDetail
