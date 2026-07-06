import { TemplateGroup } from '@/types/template-group.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Template } from '@/types/template.ts'

import {
    createTemplate,
    templateQueries,
    templateSchema,
} from '@/features/template-groups/templates/api.ts'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import {
    CheckboxItemForm,
    InputForm,
    TextareaForm,
} from '@/components/ui/Forms'

interface Props {
    templateGroup: TemplateGroup
    onClose: () => void
}

const CreateTemplateCard = ({ templateGroup, onClose }: Props) => {
    const mutate = useQueryMutator<Template[]>(
        templateQueries.list(templateGroup.uuid, {}).queryKey
    )

    const form = useForm({
        resolver: zodResolver(templateSchema),
        defaultValues: {
            name: '',
            description: '',
            vmid: '',
            isAdminOnly: false,
        },
    })

    const submit = async (data: z.infer<typeof templateSchema>) => {
        try {
            const template = await createTemplate(templateGroup.uuid, data)

            await mutate(templates => {
                if (!templates) return templates
                return [...templates, template].sort((a, b) =>
                    a.name.localeCompare(b.name)
                )
            })

            onClose()
        } catch (e) {
            if (handleFormErrors(e, form.setError)) {
                toast.error('Failed to create template')
            }
        }
    }

    return (
        <div className={'border-b py-2'}>
            <Form {...form}>
                <form
                    className={'space-y-2'}
                    onSubmit={form.handleSubmit(submit as any)}
                >
                    <InputForm name={'name'} label={'Name'} />
                    <TextareaForm name={'description'} label={'Description'} />
                    <InputForm name={'vmid'} label={'VMID'} type={'number'} />
                    <CheckboxItemForm
                        name={'isAdminOnly'}
                        label={'Admin Only'}
                    />

                    <div className={'flex justify-end gap-2'}>
                        <Button
                            variant={'secondary'}
                            onClick={() => onClose()}
                            size={'sm'}
                            type={'button'}
                        >
                            Cancel
                        </Button>
                        <FormButton size={'sm'}>Create template</FormButton>
                    </div>
                </form>
            </Form>
        </div>
    )
}

export default CreateTemplateCard
