import { toast } from 'sonner'
import useSWRMutation from 'swr/mutation'
import { useShallow } from 'zustand/react/shallow'

import deleteTemplateGroup from '@/api/admin/templateGroups/deleteTemplateGroup.ts'
import useTemplateGroupsSWR from '@/api/admin/templateGroups/use-template-groups-swr.ts'

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
    const { mutate } = useTemplateGroupsSWR({})
    const { isOpen, modalData, closeModal } = useTemplateGroupsModalStore(
        useShallow(state => ({
            isOpen: state.activeModal === 'delete',
            modalData: state.modalData,
            closeModal: state.closeModal,
        }))
    )

    const { trigger, isMutating } = useSWRMutation(
        ['delete-template-group', modalData?.uuid],
        async (_, { arg: uuid }: { arg: string }) => {
            return deleteTemplateGroup(uuid)
        },
        {
            onSuccess: () => {
                mutate(
                    currentData => {
                        if (!currentData || !modalData) return currentData
                        return currentData.filter(
                            group => group.uuid !== modalData.uuid
                        )
                    },
                    { revalidate: false }
                )
                closeModal('delete')
                toast.success('Template group deleted')
            },
            onError: e => {
                toast.error('Failed to delete template group')
                throw e
            },
        }
    )

    const submit = async () => {
        if (!modalData) return
        await trigger(modalData.uuid)
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
