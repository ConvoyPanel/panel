import { PaginatedAddresses } from '@/types/address.ts'
import { useParams } from '@tanstack/react-router'
import { useMutation } from '@tanstack/react-query'
import { toast } from 'sonner'
import { Mutator } from '@/types/query.ts'
import { useShallow } from 'zustand/react/shallow'

import { deleteAddress } from '@/features/ipam/blocks/addresses/api.ts'

import { useAddressModal } from '@/features/ipam/hooks/use-address-modal.ts'

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

interface Props {
    mutate: Mutator<PaginatedAddresses>
}

const DeleteAddressModal = ({ mutate }: Props) => {
    const { addressBlockGroupId, addressBlockId } = useParams({
        strict: false,
    }) as { addressBlockGroupId: string; addressBlockId: string }

    const [address, open, closeModal] = useAddressModal(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'delete',
            state.closeModal,
        ])
    )

    const { mutate: deleteAddressTrigger, isPending: isMutating } = useMutation(
        {
            mutationFn: async () => {
                if (!address) return

                await deleteAddress(
                    Number(addressBlockGroupId),
                    Number(addressBlockId),
                    address.id
                )

                await mutate(data => {
                    if (!data) return
                    return {
                        ...data,
                        items: data.items.filter(
                            item => item.id !== address.id
                        ),
                    }
                }, false)

                closeModal('delete')
                toast.success('Address deleted')
            },
            onError: () => {
                toast.error('Failed to delete address')
            },
        }
    )

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={open => !open && closeModal('delete')}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Delete {address?.ip}</ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to delete this address? This
                        action cannot be undone.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={
                            <Button variant={'outline'} type={'button'}>
                                Cancel
                            </Button>
                        }
                    />
                    <Button
                        variant={'destructive'}
                        onClick={() => deleteAddressTrigger()}
                        loading={isMutating}
                    >
                        Delete
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default DeleteAddressModal
