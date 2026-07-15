import useAsyncFunction from '@/hooks/use-async-function.ts'
import { PaginatedAddressBlockGroups } from '@/types/address-block-group.ts'
import { AxiosError } from 'axios'
import { toast } from 'sonner'
import { Mutator } from '@/types/query.ts'
import { useShallow } from 'zustand/react/shallow'

import { deleteAddressBlockGroup } from '@/features/ipam/api.ts'

import useBlockGroupModalStore from '@/features/ipam/hooks/use-block-group-modal-store.ts'

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
    mutate: Mutator<PaginatedAddressBlockGroups>
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
        <ResponsiveDialog open={open} onOpenChange={open => !open && close('delete')}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Delete {blockGroup?.name}</ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to delete this block group? This
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

export default DeleteBlockGroupModal
