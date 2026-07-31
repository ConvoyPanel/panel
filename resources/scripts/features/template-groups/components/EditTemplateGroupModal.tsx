import {
    templateGroupQueries,
    templateGroupSchema,
    updateTemplateGroup,
} from '@/features/template-groups/api.ts'
import TemplateIconSelect from '@/features/template-groups/components/TemplateIconSelect.tsx'
import useTemplateGroupsModalStore from '@/features/template-groups/hooks/use-template-groups-modal-store.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { TemplateGroup, TemplateIcon } from '@/types/template-group.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { CheckboxForm, InputForm, TextareaForm } from '@/components/ui/Forms'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

const EditTemplateGroupModal = () => {
    const mutate = useQueryMutator<TemplateGroup[]>(
        templateGroupQueries.list({}).queryKey
    )

    const {
        open: isOpen,
        data: modalData,
        close,
    } = useModal(useTemplateGroupsModalStore, 'edit')

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
        mutationFn: (arg: z.infer<typeof templateGroupSchema>) => {
            if (!modalData) throw new Error('No template group selected')

            return updateTemplateGroup(modalData.uuid, arg)
        },
        onSuccess: updatedGroup => {
            mutate((currentData: TemplateGroup[] | undefined) => {
                if (!currentData) return
                return currentData.map(group =>
                    group.uuid === updatedGroup.uuid ? updatedGroup : group
                )
            }, false)

            close()
            toast.add({ title: 'Template group updated', type: 'success' })
        },
        onError: e => {
            handleFormErrors(e, form.setError)
            toast.add({ title: 'Failed to save changes', type: 'error' })
        },
    })

    const submit = (data: z.input<typeof templateGroupSchema>) => {
        trigger(data as z.infer<typeof templateGroupSchema>)
    }

    return (
        <ResponsiveDialog open={isOpen} onOpenChange={() => close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Edit Template Group
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody className={'space-y-4'}>
                            <InputForm name={'name'} label={'Name'} />
                            <TextareaForm
                                name={'description'}
                                label={'Description'}
                                description={
                                    'This is visible to non-admins too.'
                                }
                            />
                            <TemplateIconSelect />
                            <CheckboxForm
                                name={'isAdminOnly'}
                                label={'Admin only'}
                                description={
                                    'If checked, this template group will only be accessible to admin users.'
                                }
                            />
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Save changes</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default EditTemplateGroupModal
