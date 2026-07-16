import { useModal } from '@/hooks/create-modal-store.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import { PaginatedAddressBlocks } from '@/types/address-block.ts'
import { toast } from 'sonner'
import { Mutator } from '@/types/query.ts'

import { deleteAddressBlock } from '@/features/ipam/blocks/api.ts'

import { useAddressBlockModal } from '@/features/ipam/hooks/use-address-block-modal.ts'

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
    addressBlockGroupId: number
    mutate: Mutator<PaginatedAddressBlocks>
}

const DeleteAddressBlockModal = ({ addressBlockGroupId, mutate }: Props) => {
    const { open, data: addressBlock, close } = useModal(useAddressBlockModal, 'delete')

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
            close()
        } catch (e) {
            toast.error('Deletion failed')
            throw e
        }
    })

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Delete {addressBlock?.name || 'Address Block'}</ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to delete this address block? This
                        action cannot be undone.
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
                        onClick={submit}
                    >
                        Delete
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default DeleteAddressBlockModal