import useAsyncFunction from '@/hooks/use-async-function.ts'
import { Route as StorageRoute } from '@/routes/_app/admin/nodes.$nodeId/storages.tsx'
import { NodeStorage } from '@/types/storage.ts'
import { toast } from 'sonner'
import { useShallow } from 'zustand/react/shallow'

import useStoragesSWR from '@/api/admin/nodes/storages/use-storages-swr.ts'

import useStoragesModalStore from '@/components/interfaces/Admin/Node/Storages/use-storages-modal-store.ts'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaClose,
    CredenzaContent,
    CredenzaDescription,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'
import deleteStorage from '@/api/admin/nodes/storages/deleteStorage.ts'

const DeleteStorageModal = () => {
    const { mutate } = useStoragesSWR()
    const { nodeId } = StorageRoute.useParams()
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
        <Credenza open={open} onOpenChange={open => !open && close('delete')}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>
                        Delete {storage?.displayName ?? storage?.name}
                    </CredenzaTitle>
                    <CredenzaDescription>
                        Are you sure you want to delete this storage? This will
                        not delete the data on the storage.
                    </CredenzaDescription>
                </CredenzaHeader>
                <CredenzaFooter className={'mt-4'}>
                    <CredenzaClose asChild>
                        <Button variant={'outline'}>Cancel</Button>
                    </CredenzaClose>
                    <Button
                        autoFocus
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={() => submit(storage!)}
                    >
                        Delete
                    </Button>
                </CredenzaFooter>
            </CredenzaContent>
        </Credenza>
    )
}

export default DeleteStorageModal
