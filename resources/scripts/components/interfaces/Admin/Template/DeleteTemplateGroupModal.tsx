import { useMutation } from '@tanstack/react-query'
import { toast } from 'sonner'
import { useShallow } from 'zustand/react/shallow'

import deleteTemplateGroup from '@/api/admin/templateGroups/deleteTemplateGroup.ts'
import { templateGroupQueries } from '@/api/admin/templateGroups/use-template-groups.ts'
import { TemplateGroup } from '@/types/template-group.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'

import useTemplateGroupsModalStore from '@/components/interfaces/Admin/Template/use-template-groups-modal-store.ts'

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
        <Credenza open={isOpen} onOpenChange={() => closeModal('delete')}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Delete Template Group</CredenzaTitle>
                    <CredenzaDescription>
                        Are you sure you want to delete this template group?
                        This action cannot be undone.
                    </CredenzaDescription>
                </CredenzaHeader>
                <CredenzaFooter className={'mt-4'}>
                    <CredenzaClose asChild>
                        <Button
                            variant={'outline'}
                            type={'button'}
                            disabled={isMutating}
                        >
                            Cancel
                        </Button>
                    </CredenzaClose>
                    <Button
                        onClick={submit}
                        loading={isMutating}
                        variant={'destructive'}
                    >
                        Delete
                    </Button>
                </CredenzaFooter>
            </CredenzaContent>
        </Credenza>
    )
}

export default DeleteTemplateGroupModal
