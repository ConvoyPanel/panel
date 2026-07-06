import useAsyncFunction from '@/hooks/use-async-function.ts'
import { PaginatedAddressBlocks } from '@/types/address-block.ts'
import { toast } from 'sonner'
import { Mutator } from '@/types/query.ts'
import { useShallow } from 'zustand/react/shallow'

import deleteAddressBlock from '@/api/admin/addressBlockGroups/addressBlocks/deleteAddressBlock.ts'

import { useAddressBlockModal } from '@/components/interfaces/Admin/Ipam/AddressBlock/use-address-block-modal.ts'

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
    addressBlockGroupId: number
    mutate: Mutator<PaginatedAddressBlocks>
}

const DeleteAddressBlockModal = ({ addressBlockGroupId, mutate }: Props) => {
    const [addressBlock, open, close] = useAddressBlockModal(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'delete',
            state.closeModal,
        ])
    )

    const [state, submit] = useAsyncFunction(async () => {
        try {
            if (!addressBlock) return

            await deleteAddressBlock(addressBlockGroupId, addressBlock.id)

            await mutate(data => {
                if (!data) return

                return {
                    ...data,
                    items: data.items.filter(item => item.id !== addressBlock.id),
                }
            }, false)

            toast.success('Address block deleted')
            close('delete')
        } catch (e) {
            toast.error('Deletion failed')
            throw e
        }
    })

    return (
        <Credenza open={open} onOpenChange={open => !open && close('delete')}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Delete {addressBlock?.name || 'Address Block'}</CredenzaTitle>
                    <CredenzaDescription>
                        Are you sure you want to delete this address block? This
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

export default DeleteAddressBlockModal