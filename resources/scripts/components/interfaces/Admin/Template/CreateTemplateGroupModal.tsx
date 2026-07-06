import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import createTemplateGroup, {
    templateGroupSchema,
} from '@/api/admin/templateGroups/createTemplateGroup.ts'
import { getKey } from '@/api/admin/templateGroups/use-template-groups.ts'
import { TemplateGroup } from '@/types/template-group.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'

import TemplateIconSelect from '@/components/interfaces/Admin/Template/TemplateIconSelect.tsx'

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

const CreateTemplateGroupModal = () => {
    const mutate = useQueryMutator<TemplateGroup[]>(getKey({}))
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
            const templateGroup = await createTemplateGroup(data as z.infer<typeof templateGroupSchema>)

            await mutate(currentData => {
                if (!currentData) return
                return [...currentData, templateGroup].sort((a, b) =>
                    a.name.localeCompare(b.name)
                )
            }, false)

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
                            <FormButton>Add template group</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default CreateTemplateGroupModal
