import { deleteAddressBlockGroup } from '@/features/ipam/api.ts'
import useBlockGroupModalStore from '@/features/ipam/hooks/use-block-group-modal-store.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import { PaginatedAddressBlockGroups } from '@/types/address-block-group.ts'
import { Mutator } from '@/types/query.ts'
import { AxiosError } from 'axios'

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

interface Props {
    mutate: Mutator<PaginatedAddressBlockGroups>
}

const DeleteBlockGroupModal = ({ mutate }: Props) => {
    const {
        open,
        data: blockGroup,
        close,
    } = useModal(useBlockGroupModalStore, 'delete')

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

            toast.add({ title: 'Block group deleted', type: 'success' })
            close()
        } catch (e) {
            // Check if it's an Axios error with a response
            if (e instanceof AxiosError && e.response) {
                // For authorization failures (403 Forbidden)
                if (e.response.status === 403) {
                    // If there's a specific message about servers being attached
                    const message =
                        e.response.data.message || 'Deletion not authorized'
                    toast.add({ title: message, type: 'error' })
                } else {
                    toast.add({ title: 'Deletion failed', type: 'error' })
                }
            } else {
                toast.add({ title: 'Deletion failed', type: 'error' })
            }

            throw e
        }
    })

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Delete {blockGroup?.name}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to delete this block group? This
                        action cannot be undone.
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
