import { attachStorage, storageQueries } from '@/features/nodes/storages/api.ts'
import { storageSummary } from '@/features/nodes/storages/capacity.ts'
import { NodeStorage } from '@/features/nodes/types.ts'
import { Route as StorageRoute } from '@/routes/_app/admin/nodes.$nodeId/storages.tsx'
import { getApiErrorMessage } from '@/utils/http.ts'
import { IconLink } from '@tabler/icons-react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'

import { Button } from '@/components/ui/Button'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemGroup,
    ItemTitle,
} from '@/components/ui/Item'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'

/**
 * Points a storage another node in the same PVE cluster already has at this one.
 *
 * Deliberately not a form. Everything that makes a storage attachable -- same
 * cluster, reported by Proxmox here, not already attached -- is knowable before
 * the operator picks, so the server returns only valid candidates and this is a
 * list of them. There is nothing to get wrong and so nothing to validate.
 */
const AttachStorageModal = () => {
    const { nodeId } = StorageRoute.useParams()
    const id = Number(nodeId)
    const [open, setOpen] = useState(false)
    const [pending, setPending] = useState<number | null>(null)
    const queryClient = useQueryClient()

    const { data: candidates, isLoading } = useQuery({
        ...storageQueries.attachable(id),
        // Asking costs a live Proxmox call, so it waits until the dialog opens.
        enabled: open,
    })

    const attach = async (storage: NodeStorage) => {
        setPending(storage.id)

        try {
            await attachStorage(id, storage.id)
            await queryClient.invalidateQueries({
                queryKey: storageQueries.all(id),
            })
            toast.add({
                title: `${storage.displayName ?? storage.name} is now available on this node`,
                type: 'success',
            })
            setOpen(false)
        } catch (error) {
            toast.add({
                title: getApiErrorMessage(error, 'Failed to attach storage'),
                type: 'error',
            })
        } finally {
            setPending(null)
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={setOpen}>
            <ResponsiveDialogTrigger
                render={
                    <Button variant='outline'>
                        <IconLink className={'size-4'} /> Attach existing
                    </Button>
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Attach existing storage
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Storage already registered on another node of this
                        Proxmox cluster, which this node can also reach.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody>
                    {isLoading ? (
                        <div className='flex flex-col gap-2'>
                            {Array.from({ length: 2 }, (_, index) => (
                                <Skeleton key={index} className='h-14' />
                            ))}
                        </div>
                    ) : candidates?.length ? (
                        <ItemGroup className='gap-2'>
                            {candidates.map(storage => (
                                <Item
                                    key={storage.id}
                                    variant='muted'
                                    size='sm'
                                >
                                    <ItemContent className='min-w-0'>
                                        <ItemTitle className='w-full min-w-0'>
                                            <span className='truncate'>
                                                {storage.displayName ??
                                                    storage.name}
                                            </span>
                                        </ItemTitle>
                                        <ItemDescription className='block truncate text-nowrap'>
                                            {storageSummary(storage)}
                                        </ItemDescription>
                                    </ItemContent>
                                    <ItemActions>
                                        <Button
                                            size='sm'
                                            variant='outline'
                                            loading={pending === storage.id}
                                            onClick={() => attach(storage)}
                                        >
                                            Attach
                                        </Button>
                                    </ItemActions>
                                </Item>
                            ))}
                        </ItemGroup>
                    ) : (
                        /*
                         * Nothing to offer is the ordinary answer on a
                         * standalone host, so this explains rather than reports
                         * an error -- there is nothing wrong.
                         */
                        <p className='text-muted-foreground text-sm'>
                            Nothing to attach. This node is either standalone,
                            or every storage its cluster has registered is
                            already here.
                        </p>
                    )}
                </ResponsiveDialogBody>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AttachStorageModal
