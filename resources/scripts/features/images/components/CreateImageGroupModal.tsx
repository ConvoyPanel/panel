import {
    createImageGroup,
    imageGroupQueries,
    imageGroupSchema,
} from '@/features/images/api.ts'
import ImageIconSelect from '@/features/images/components/ImageIconSelect.tsx'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { ImageGroup } from '@/types/image.ts'
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

const CreateImageGroupModal = () => {
    const mutate = useQueryMutator<ImageGroup[]>(
        imageGroupQueries.list({}).queryKey
    )
    const [open, setOpen] = useState(false)

    const form = useForm<z.input<typeof imageGroupSchema>>({
        resolver: zodResolver(imageGroupSchema),
        defaultValues: {
            name: '',
            description: '',
            icon: null,
            isAdminOnly: false,
        },
    })

    const submit = async (data: z.input<typeof imageGroupSchema>) => {
        try {
            const imageGroup = await createImageGroup(
                data as z.infer<typeof imageGroupSchema>
            )

            await mutate(currentData => {
                if (!currentData) return
                return [...currentData, imageGroup].sort((a, b) =>
                    a.name.localeCompare(b.name)
                )
            }, false)

            form.reset()
            setOpen(false)
            toast.add({
                title: 'Image group created',
                type: 'success',
            })
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
                        <IconPlus className={'size-4'} /> Add image group
                    </Button>
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        New image group
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
                            <ImageIconSelect />
                            <CheckboxForm
                                name={'isAdminOnly'}
                                label={'Admin only'}
                                description={
                                    'If checked, this image group will only be accessible to admin users.'
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
                            <FormButton>Add image group</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default CreateImageGroupModal
