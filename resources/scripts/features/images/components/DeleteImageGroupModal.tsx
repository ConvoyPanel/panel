import { deleteImageGroup, imageGroupQueries } from '@/features/images/api.ts'
import useImageGroupsModalStore from '@/features/images/hooks/use-image-groups-modal-store.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { ImageGroup } from '@/types/image.ts'
import { useMutation } from '@tanstack/react-query'

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

const DeleteImageGroupModal = () => {
    const mutate = useQueryMutator<ImageGroup[]>(
        imageGroupQueries.list({}).queryKey
    )
    const {
        open: isOpen,
        data: modalData,
        close,
    } = useModal(useImageGroupsModalStore, 'delete')

    const { mutate: trigger, isPending: isMutating } = useMutation({
        mutationFn: (uuid: string) => deleteImageGroup(uuid),
        onSuccess: () => {
            mutate(currentData => {
                if (!currentData || !modalData) return currentData
                return currentData.filter(
                    group => group.uuid !== modalData.uuid
                )
            }, false)
            close()
            toast.add({
                title: 'Image group deleted',
                type: 'success',
            })
        },
        onError: () => {
            toast.add({
                title: 'Failed to delete image group',
                type: 'error',
            })
        },
    })

    const submit = () => {
        if (!modalData) return
        trigger(modalData.uuid)
    }

    return (
        <ResponsiveDialog open={isOpen} onOpenChange={() => close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Delete image group
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to delete this image group? This
                        action cannot be undone.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogFooter className={'mt-4'}>
                    <ResponsiveDialogClose
                        render={
                            <Button
                                variant={'outline'}
                                type={'button'}
                                disabled={isMutating}
                            >
                                Cancel
                            </Button>
                        }
                    />
                    <Button
                        onClick={submit}
                        loading={isMutating}
                        variant={'destructive'}
                    >
                        Delete
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default DeleteImageGroupModal
