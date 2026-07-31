import useStoragesModalStore from '@/features/nodes/hooks/use-storages-modal-store.ts'
import { storageQueries } from '@/features/nodes/storages/api.ts'
import { deleteStorage } from '@/features/nodes/storages/api.ts'
import { NodeStorage } from '@/features/nodes/types.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Route as StorageRoute } from '@/routes/_app/admin/nodes.$nodeId/storages.tsx'

import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

const DeleteStorageModal = () => {
    const { nodeId } = StorageRoute.useParams()
    const mutate = useQueryMutator<NodeStorage[]>(
        storageQueries.all(Number(nodeId))
    )
    const {
        open,
        data: storage,
        close,
    } = useModal(useStoragesModalStore, 'delete')

    const [state, submit] = useAsyncFunction(
        async (currentStorage: NodeStorage) => {
            try {
                await deleteStorage(Number(nodeId), currentStorage.id)

                await mutate(data => {
                    if (!data) return data

                    return data.filter(item => item.id !== currentStorage.id)
                }, false)

                toast.add({ title: 'Storage deleted', type: 'success' })
                close()
            } catch (e) {
                toast.add({ title: 'Deletion failed', type: 'error' })
                throw e
            }
        }
    )

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Delete {storage?.displayName ?? storage?.name}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to delete this storage? This will
                        not delete the data on the storage.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogFooter className={'mt-4'}>
                    <ResponsiveDialogClose
                        render={<Button variant={'outline'}>Cancel</Button>}
                    />
                    <Button
                        autoFocus
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={() => storage && submit(storage)}
                    >
                        Delete
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default DeleteStorageModal
