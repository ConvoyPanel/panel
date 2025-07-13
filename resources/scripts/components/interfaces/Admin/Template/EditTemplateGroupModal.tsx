import { TemplateGroup, TemplateIcon } from '@/types/template-group.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import useSWRMutation from 'swr/mutation'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import { templateGroupSchema } from '@/api/admin/templateGroups/createTemplateGroup.ts'
import updateTemplateGroup from '@/api/admin/templateGroups/updateTemplateGroup.ts'
import useTemplateGroupsSWR, {
    getKey,
} from '@/api/admin/templateGroups/use-template-groups-swr.ts'

import useTemplateGroupsModalStore from '@/components/interfaces/Admin/Template/use-template-groups-modal-store.ts'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'
import { Form, FormButton } from '@/components/ui/Form'
import { CheckboxForm, InputForm, TextareaForm } from '@/components/ui/Forms'
import TemplateIconSelect from '@/components/interfaces/Admin/Template/TemplateIconSelect.tsx'

const EditTemplateGroupModal = () => {
    const { mutate } = useTemplateGroupsSWR({})

    const { isOpen, modalData, closeModal } = useTemplateGroupsModalStore(
        useShallow(state => ({
            isOpen: state.activeModal === 'edit',
            modalData: state.modalData,
            closeModal: state.closeModal,
        }))
    )

    const form = useForm<z.infer<typeof templateGroupSchema>>({
        resolver: zodResolver(templateGroupSchema),
    })

    useEffect(() => {
        if (modalData) {
            form.reset({
                name: modalData.name,
                description: modalData.description ?? '',
                icon: modalData.icon as TemplateIcon,
                isAdminOnly: modalData.isAdminOnly,
            })
        }
    }, [modalData])

    const { trigger } = useSWRMutation(
        getKey({}),
        async (
            _key,
            { arg }: { arg: z.infer<typeof templateGroupSchema> }
        ) => {
            return await updateTemplateGroup(modalData!.uuid, arg)
        },
        {
            onSuccess: updatedGroup => {
                mutate(
                    (currentData: TemplateGroup[] | undefined) => {
                        if (!currentData) return
                        return currentData.map(group =>
                            group.uuid === updatedGroup.uuid ? updatedGroup : group
                        )
                    },
                    { revalidate: false }
                )

                closeModal('edit')
                toast.success('Template group updated')
            },
            onError: e => {
                handleFormErrors(e, form.setError)
                toast.error('Failed to save changes')
                throw e
            },
        }
    )

    const submit = async (data: z.infer<typeof templateGroupSchema>) => {
        await trigger(data)
    }

    return (
        <Credenza open={isOpen} onOpenChange={() => closeModal('edit')}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Edit Template Group</CredenzaTitle>
                </CredenzaHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <CredenzaBody className={'space-y-4'}>
                            <InputForm name={'name'} label={'Name'} />
                            <TextareaForm
                                name={'description'}
                                label={'Description'}
                            />
                            <TemplateIconSelect />
                            <CheckboxForm
                                name={'isAdminOnly'}
                                label={'Admin only'}
                                description={
                                    'If checked, this template group will only be accessible to admin users.'
                                }
                            />
                        </CredenzaBody>
                        <CredenzaFooter className={'mt-4'}>
                            <CredenzaClose asChild>
                                <Button variant={'outline'} type={'button'}>
                                    Cancel
                                </Button>
                            </CredenzaClose>
                            <FormButton>Save changes</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default EditTemplateGroupModal
