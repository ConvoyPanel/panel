import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import createTemplateGroup, {
    createTemplateGroupSchema,
} from '@/api/admin/templateGroups/createTemplateGroup.ts'
import useTemplateGroupsSWR from '@/api/admin/templateGroups/use-template-groups-swr.ts'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
    CredenzaTrigger,
} from '@/components/ui/Credenza'
import { Form, FormButton } from '@/components/ui/Form'
import { CheckboxForm, InputForm, TextareaForm } from '@/components/ui/Forms'
import TemplateIconSelect from '@/components/interfaces/Admin/Template/TemplateIconSelect.tsx'

const CreateTemplateGroupModal = () => {
    const { mutate } = useTemplateGroupsSWR({})
    const [open, setOpen] = useState(false)

    const form = useForm<z.infer<typeof createTemplateGroupSchema>>({
        resolver: zodResolver(createTemplateGroupSchema),
        defaultValues: {
            name: '',
            description: '',
            icon: null,
            isAdminOnly: false,
        },
    })

    const submit = async (data: z.infer<typeof createTemplateGroupSchema>) => {
        try {
            const templateGroup = await createTemplateGroup(data)

            await mutate(
                currentData => {
                    if (!currentData) return
                    return [...currentData, templateGroup]
                },
                { revalidate: false }
            )

            form.reset()
            setOpen(false)
            toast.success('Template group created')
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to save changes')
            throw e
        }
    }

    return (
        <Credenza open={open} onOpenChange={setOpen}>
            <CredenzaTrigger asChild>
                <Button size={'sm'} className={'self-end'}>
                    <IconPlus className={'mr-2 size-4'} /> Add template group
                </Button>
            </CredenzaTrigger>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>New Template Group</CredenzaTitle>
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
                            <FormButton>Add template group</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default CreateTemplateGroupModal
