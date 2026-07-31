import {
    deleteTemplateGroup,
    templateGroupQueries,
} from '@/features/template-groups/api.ts'
import useTemplateGroupsModalStore from '@/features/template-groups/hooks/use-template-groups-modal-store.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { TemplateGroup } from '@/types/template-group.ts'
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

const DeleteTemplateGroupModal = () => {
    const mutate = useQueryMutator<TemplateGroup[]>(
        templateGroupQueries.list({}).queryKey
    )
    const {
        open: isOpen,
        data: modalData,
        close,
    } = useModal(useTemplateGroupsModalStore, 'delete')

    const { mutate: trigger, isPending: isMutating } = useMutation({
        mutationFn: (uuid: string) => deleteTemplateGroup(uuid),
        onSuccess: () => {
            mutate(currentData => {
                if (!currentData || !modalData) return currentData
                return currentData.filter(
                    group => group.uuid !== modalData.uuid
                )
            }, false)
            close()
            toast.add({ title: 'Template group deleted', type: 'success' })
        },
        onError: () => {
            toast.add({
                title: 'Failed to delete template group',
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
                        Delete Template Group
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to delete this template group?
                        This action cannot be undone.
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

export default DeleteTemplateGroupModal
