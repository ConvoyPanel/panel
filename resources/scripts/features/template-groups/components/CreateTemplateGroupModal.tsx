import {
    createTemplateGroup,
    templateGroupQueries,
    templateGroupSchema,
} from '@/features/template-groups/api.ts'
import TemplateIconSelect from '@/features/template-groups/components/TemplateIconSelect.tsx'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { TemplateGroup } from '@/types/template-group.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useState } from 'react'
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
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

const CreateTemplateGroupModal = () => {
    const mutate = useQueryMutator<TemplateGroup[]>(
        templateGroupQueries.list({}).queryKey
    )
    const [open, setOpen] = useState(false)

    const form = useForm<z.input<typeof templateGroupSchema>>({
        resolver: zodResolver(templateGroupSchema),
        defaultValues: {
            name: '',
            description: '',
            icon: null,
            isAdminOnly: false,
        },
    })

    const submit = async (data: z.input<typeof templateGroupSchema>) => {
        try {
            const templateGroup = await createTemplateGroup(
                data as z.infer<typeof templateGroupSchema>
            )

            await mutate(currentData => {
                if (!currentData) return
                return [...currentData, templateGroup].sort((a, b) =>
                    a.name.localeCompare(b.name)
                )
            }, false)

            form.reset()
            setOpen(false)
            toast.add({ title: 'Template group created', type: 'success' })
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({ title: 'Failed to save changes', type: 'error' })
            throw e
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={setOpen}>
            <ResponsiveDialogTrigger
                render={
                    <Button>
                        <IconPlus className={'size-4'} /> Add template group
                    </Button>
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        New Template Group
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
                            <FormButton>Add template group</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default CreateTemplateGroupModal
