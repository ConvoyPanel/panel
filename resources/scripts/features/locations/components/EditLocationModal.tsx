import { updateLocation } from '@/features/locations/api.ts'
import {
    PaginatedLocations,
    locationSchema,
} from '@/features/locations/types.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import { useLocationsModalStore } from '@/routes/_app/admin/_dashboard/locations.lazy.tsx'
import { Mutator } from '@/types/query.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
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

interface Props {
    mutate: Mutator<PaginatedLocations>
}

const EditLocationModal = ({ mutate }: Props) => {
    const {
        open,
        data: location,
        close,
    } = useModal(useLocationsModalStore, 'edit')

    const form = useForm({
        resolver: zodResolver(locationSchema),
        defaultValues: {
            shortCode: '',
            description: '',
        },
    })

    useEffect(() => {
        if (!location) return

        form.reset({
            shortCode: location.shortCode,
            description: location.description ?? '',
        })
    }, [location])

    const submit = async (data: z.infer<typeof locationSchema>) => {
        if (!location) return

        try {
            const updatedLocation = await updateLocation(location.id, data)

            await mutate(data => {
                if (!data) return

                return {
                    ...data,
                    items: data.items.map(item =>
                        item.id === updatedLocation.id ? updatedLocation : item
                    ),
                }
            }, false)

            toast.add({ title: 'Location updated', type: 'success' })

            close()
        } catch (e) {
            toast.add({ title: 'Failed to save changes', type: 'error' })
            throw e
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Editing {location?.shortCode}
                    </ResponsiveDialogTitle>
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
                            <FormButton>Save</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default EditLocationModal
