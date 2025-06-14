import useAsyncFunction from '@/hooks/use-async-function.ts'
import { PaginatedAddressBlockGroups } from '@/types/address-block-group.ts'
import { AxiosError } from 'axios'
import { toast } from 'sonner'
import { KeyedMutator } from 'swr'
import { useShallow } from 'zustand/react/shallow'

import deleteAddressBlockGroup from '@/api/admin/addressBlockGroups/deleteAddressBlockGroup.ts'

import useBlockGroupModalStore from '@/components/interfaces/Admin/Ipam/use-block-group-modal-store.ts'

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

interface Props {
    mutate: KeyedMutator<PaginatedAddressBlockGroups>
}

const DeleteBlockGroupModal = ({ mutate }: Props) => {
    const [blockGroup, open, close] = useBlockGroupModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'delete',
            state.closeModal,
        ])
    )

    const [state, submit] = useAsyncFunction(async () => {
        try {
            if (!blockGroup) return

            await deleteAddressBlockGroup(blockGroup.id)

            await mutate(data => {
                if (!data) return

                return {
                    ...data,
                    items: data.items.filter(item => item.id !== blockGroup.id),
                }
            }, false)

            toast.success('Block group deleted')
            close('delete')
        } catch (e) {
            // Check if it's an Axios error with a response
            if (e instanceof AxiosError && e.response) {
                // For authorization failures (403 Forbidden)
                if (e.response.status === 403) {
                    // If there's a specific message about servers being attached
                    const message =
                        e.response.data.message || 'Deletion not authorized'
                    toast.error(message)
                } else {
                    toast.error('Deletion failed')
                }
            } else {
                toast.error('Deletion failed')
            }
            
            throw e
        }
    })

    return (
        <Credenza open={open} onOpenChange={open => !open && close('delete')}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Delete {blockGroup?.name}</CredenzaTitle>
                    <CredenzaDescription>
                        Are you sure you want to delete this block group? This
                        action cannot be undone.
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
                        onClick={submit}
                    >
                        Delete
                    </Button>
                </CredenzaFooter>
            </CredenzaContent>
        </Credenza>
    )
}

export default DeleteBlockGroupModal
