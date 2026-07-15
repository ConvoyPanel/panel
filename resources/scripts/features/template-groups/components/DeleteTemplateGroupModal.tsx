import { useMutation } from '@tanstack/react-query'
import { toast } from 'sonner'
import { useShallow } from 'zustand/react/shallow'

import {
    deleteTemplateGroup,
    templateGroupQueries,
} from '@/features/template-groups/api.ts'
import { TemplateGroup } from '@/types/template-group.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'

import useTemplateGroupsModalStore from '@/features/template-groups/hooks/use-template-groups-modal-store.ts'

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

const DeleteTemplateGroupModal = () => {
    const mutate = useQueryMutator<TemplateGroup[]>(templateGroupQueries.list({}).queryKey)
    const { isOpen, modalData, closeModal } = useTemplateGroupsModalStore(
        useShallow(state => ({
            isOpen: state.activeModal === 'delete',
            modalData: state.modalData,
            closeModal: state.closeModal,
        }))
    )

    const { mutate: trigger, isPending: isMutating } = useMutation({
        mutationFn: (uuid: string) => deleteTemplateGroup(uuid),
        onSuccess: () => {
            mutate(currentData => {
                if (!currentData || !modalData) return currentData
                return currentData.filter(
                    group => group.uuid !== modalData.uuid
                )
            }, false)
            closeModal('delete')
            toast.success('Template group deleted')
        },
        onError: () => {
            toast.error('Failed to delete template group')
        },
    })

    const submit = () => {
        if (!modalData) return
        trigger(modalData.uuid)
    }

    return (
        <ResponsiveDialog open={isOpen} onOpenChange={() => closeModal('delete')}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Delete Template Group</ResponsiveDialogTitle>
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
