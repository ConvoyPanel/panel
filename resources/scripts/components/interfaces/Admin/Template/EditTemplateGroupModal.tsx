import { TemplateGroup, TemplateIcon } from '@/types/template-group.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { useMutation } from '@tanstack/react-query'
import { toast } from 'sonner'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import {
    templateGroupQueries,
    templateGroupSchema,
    updateTemplateGroup,
} from '@/features/template-groups/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'

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
    const mutate = useQueryMutator<TemplateGroup[]>(templateGroupQueries.list({}).queryKey)

    const { isOpen, modalData, closeModal } = useTemplateGroupsModalStore(
        useShallow(state => ({
            isOpen: state.activeModal === 'edit',
            modalData: state.modalData,
            closeModal: state.closeModal,
        }))
    )

    const form = useForm<z.input<typeof templateGroupSchema>>({
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

    const { mutate: trigger } = useMutation({
        mutationFn: (arg: z.infer<typeof templateGroupSchema>) =>
            updateTemplateGroup(modalData!.uuid, arg),
        onSuccess: updatedGroup => {
            mutate((currentData: TemplateGroup[] | undefined) => {
                if (!currentData) return
                return currentData.map(group =>
                    group.uuid === updatedGroup.uuid ? updatedGroup : group
                )
            }, false)

            closeModal('edit')
            toast.success('Template group updated')
        },
        onError: e => {
            handleFormErrors(e, form.setError)
            toast.error('Failed to save changes')
        },
    })

    const submit = (data: z.input<typeof templateGroupSchema>) => {
        trigger(data as z.infer<typeof templateGroupSchema>)
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
                                description={'This is visible to non-admins too.'}
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
