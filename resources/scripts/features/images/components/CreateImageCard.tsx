import {
    createImageDefinition,
    imageDefinitionQueries,
    imageDefinitionSchema,
} from '@/features/images/definitions/api.ts'
import { OSTYPE_ITEMS } from '@/features/images/ostypes'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { ImageGroup } from '@/types/image.ts'
import { ImageDefinition } from '@/types/image.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import {
    CheckboxItemForm,
    InputForm,
    SelectForm,
    TextareaForm,
} from '@/components/ui/Forms'
import { toast } from '@/components/ui/Toast'

interface Props {
    imageGroup: ImageGroup
    onClose: () => void
}

const CreateImageCard = ({ imageGroup, onClose }: Props) => {
    const mutate = useQueryMutator<ImageDefinition[]>(
        imageDefinitionQueries.list(imageGroup.uuid, {}).queryKey
    )

    const form = useForm({
        resolver: zodResolver(imageDefinitionSchema),
        defaultValues: {
            name: '',
            description: '',
            // Sensible for the overwhelming majority of images, and the whole
            // hardware profile follows from it — so the form opens answered
            // rather than blank.
            ostype: 'l26',
            hardware: {},
            minimumCores: null,
            minimumMemory: null,
            isAdminOnly: false,
        },
    })

    const submit = async (data: z.infer<typeof imageDefinitionSchema>) => {
        try {
            const image = await createImageDefinition(imageGroup.uuid, data)

            await mutate(definitions => {
                if (!definitions) return definitions
                return [...definitions, image].sort((a, b) =>
                    a.name.localeCompare(b.name)
                )
            })

            onClose()
        } catch (e) {
            if (handleFormErrors(e, form.setError)) {
                toast.add({ title: 'Failed to create image', type: 'error' })
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
                    <SelectForm
                        name={'ostype'}
                        label={'Operating system'}
                        description={
                            'Everything else about the hardware follows from this. Change it later only if the guest changes.'
                        }
                        items={OSTYPE_ITEMS}
                    />
                    <CheckboxItemForm
                        name={'isAdminOnly'}
                        label={'Admin Only'}
                    />

                    <div className={'flex justify-end gap-2'}>
                        <Button
                            variant={'secondary'}
                            onClick={() => onClose()}
                            type={'button'}
                        >
                            Cancel
                        </Button>
                        <FormButton>Create image</FormButton>
                    </div>
                </form>
            </Form>
        </div>
    )
}

export default CreateImageCard
