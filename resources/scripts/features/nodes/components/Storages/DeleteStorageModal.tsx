import useAsyncFunction from '@/hooks/use-async-function.ts'
import { Route as StorageRoute } from '@/routes/_app/admin/nodes.$nodeId/storages.tsx'
import { NodeStorage } from '@/features/nodes/types.ts'
import { toast } from 'sonner'
import { useShallow } from 'zustand/react/shallow'

import useQueryMutator from '@/hooks/use-query-mutator.ts'

import { storageQueries } from '@/features/nodes/storages/api.ts'

import useStoragesModalStore from '@/features/nodes/hooks/use-storages-modal-store.ts'

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
import { deleteStorage } from '@/features/nodes/storages/api.ts'

const DeleteStorageModal = () => {
    const { nodeId } = StorageRoute.useParams()
    const mutate = useQueryMutator<NodeStorage[]>(
        storageQueries.all(Number(nodeId))
    )
    const [storage, open, close] = useStoragesModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'delete',
            state.closeModal,
        ])
    )

    const [state, submit] = useAsyncFunction(
        async (currentStorage: NodeStorage) => {
            try {
                await deleteStorage(Number(nodeId), currentStorage.id)

                await mutate(data => {
                    if (!data) return data

                    return data.filter(
                        item => item.id !== currentStorage.id
                    )
                }, false)

                toast.success('Storage deleted')
                close('delete')
            } catch (e) {
                toast.error('Deletion failed')
                throw e
            }
         }
    )

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close('delete')}>
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
                        render={
                            <Button variant={'outline'}>Cancel</Button>
                        }
                    />
                    <Button
                        autoFocus
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={() => submit(storage!)}
                    >
                        Delete
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default DeleteStorageModal
