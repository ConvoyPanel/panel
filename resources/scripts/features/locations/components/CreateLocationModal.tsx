import { PaginatedLocations, locationSchema } from '@/features/locations/types.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { Mutator } from '@/types/query.ts'
import { z } from 'zod'

import { createLocation } from '@/features/locations/api.ts'

import { Button } from '@/components/ui/Button'
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
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'

interface Props {
    mutate: Mutator<PaginatedLocations>
}

const CreateLocationModal = ({ mutate }: Props) => {
    const [open, setOpen] = useState(false)

    const form = useForm({
        resolver: zodResolver(locationSchema),
        defaultValues: {
            shortCode: '',
            description: '',
        },
    })

    const submit = async (data: z.infer<typeof locationSchema>) => {
        try {
            const location = await createLocation(data)

            await mutate(data => {
                if (!data) return

                return {
                    ...data,
                    items: [location, ...data.items],
                }
            }, false)

            toast.success('Location created')

            setOpen(false)
        } catch (e) {
            toast.error('Failed to save changes')
            throw e
        }
    }

    return (
        <>
                <ResponsiveDialog open={open} onOpenChange={setOpen}>
                    <ResponsiveDialogTrigger
                        render={
                        <Button>
                            <IconPlus className={'size-4'} /> Add location
                        </Button>
                        }
                    />
                <ResponsiveDialogContent>
                    <ResponsiveDialogHeader>
                        <ResponsiveDialogTitle>New Location</ResponsiveDialogTitle>
                    </ResponsiveDialogHeader>
                    <Form {...form}>
                        <form onSubmit={form.handleSubmit(submit)}>
                            <ResponsiveDialogBody className={'space-y-2'}>
                                <InputForm
                                    name={'shortCode'}
                                    label={'Short Code'}
                                />
                                <InputForm
                                    name={'description'}
                                    label={'Description'}
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
                                <FormButton>Add location</FormButton>
                            </ResponsiveDialogFooter>
                        </form>
                    </Form>
                </ResponsiveDialogContent>
            </ResponsiveDialog>
        </>
    )
}

export default CreateLocationModal
