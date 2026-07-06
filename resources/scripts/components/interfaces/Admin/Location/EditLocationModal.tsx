import { useLocationsModalStore } from '@/routes/_app/admin/_dashboard/locations.lazy.tsx'
import { PaginatedLocations, locationSchema } from '@/types/location.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { Mutator } from '@/types/query.ts'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import { updateLocation } from '@/features/locations/api.ts'

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
import { InputForm } from '@/components/ui/Forms'

interface Props {
    mutate: Mutator<PaginatedLocations>
}

const EditLocationModal = ({ mutate }: Props) => {
    const [location, open, closeModal] = useLocationsModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'edit',
            state.closeModal,
        ])
    )

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
        try {
            const updatedLocation = await updateLocation(location!.id, data)

            await mutate(data => {
                if (!data) return

                return {
                    ...data,
                    items: data.items.map(item =>
                        item.id === updatedLocation.id ? updatedLocation : item
                    ),
                }
            }, false)

            toast.success('Location updated')

            closeModal('edit')
        } catch (e) {
            toast.error('Failed to save changes')
            throw e
        }
    }

    return (
        <Credenza
            open={open}
            onOpenChange={open => !open && closeModal('edit')}
        >
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Editing {location?.shortCode}</CredenzaTitle>
                </CredenzaHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <CredenzaBody className={'space-y-2'}>
                            <InputForm
                                name={'shortCode'}
                                label={'Short Code'}
                            />
                            <InputForm
                                name={'description'}
                                label={'Description'}
                            />
                        </CredenzaBody>
                        <CredenzaFooter className={'mt-4'}>
                            <CredenzaClose asChild>
                                <Button variant={'outline'} type={'button'}>
                                    Cancel
                                </Button>
                            </CredenzaClose>
                            <FormButton>Save</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default EditLocationModal
