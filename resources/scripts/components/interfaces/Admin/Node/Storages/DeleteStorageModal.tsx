import useAsyncFunction from '@/hooks/use-async-function.ts'
import { useStoragesModalStore } from '@/routes/_app/admin/nodes.$nodeId/storages.lazy.tsx'
import { Route as StorageRoute } from '@/routes/_app/admin/nodes.$nodeId/storages.tsx'
import { NodeStorage } from '@/types/storage.ts'
import { toast } from 'sonner'
import { KeyedMutator } from 'swr'
import { useShallow } from 'zustand/react/shallow'

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
import useStoragesSWR from '@/api/admin/nodes/storages/use-storages-swr.ts'

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
