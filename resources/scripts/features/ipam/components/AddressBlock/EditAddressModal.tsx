import { updateAddress } from '@/features/ipam/blocks/addresses/api.ts'
import ServerPicker from '@/features/ipam/components/AddressBlock/ServerPicker.tsx'
import { useAddressModal } from '@/features/ipam/hooks/use-address-modal.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import { PaginatedAddresses } from '@/types/address.ts'
import { Mutator } from '@/types/query.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useParams } from '@tanstack/react-router'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
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

const addressSchema = z.object({
    serverId: z
        .string()
        .nullable()
        .transform(val => val || null),
})

interface Props {
    mutate: Mutator<PaginatedAddresses>
}

const EditAddressModal = ({ mutate }: Props) => {
    const { addressBlockGroupId, addressBlockId } = useParams({
        strict: false,
    }) as { addressBlockGroupId: string; addressBlockId: string }
    const { open, data: address, close } = useModal(useAddressModal, 'edit')

    const form = useForm({
        resolver: zodResolver(addressSchema),
        defaultValues: {
            serverId: '',
        },
    })

    useEffect(() => {
        if (!address) return

        form.reset({
            serverId: address.serverId?.toString() ?? '',
        })
    }, [address])

    const submit = async (data: z.infer<typeof addressSchema>) => {
        try {
            if (!address) return

            const updatedAddress = await updateAddress(
                Number(addressBlockGroupId),
                Number(addressBlockId),
                address.id,
                data.serverId ? Number(data.serverId) : null
            )

            await mutate(data => {
                if (!data) return
                return {
                    ...data,
                    items: data.items.map(item => {
                        if (item.id === updatedAddress.id) {
                            return updatedAddress
                        }
                        return item
                    }),
                }
            }, false)

            close()
            toast.add({ title: 'Address updated', type: 'success' })
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({ title: 'Failed to save changes', type: 'error' })
            throw e
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Editing Address {address?.ip}
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <ResponsiveDialogBody>
                            <ServerPicker
                                addressBlockGroupId={Number(
                                    addressBlockGroupId
                                )}
                            />
                            <Button
                                variant={'link'}
                                className={'ml-auto block px-0'}
                                onClick={() => form.setValue('serverId', '')}
                                type='button'
                            >
                                Clear
                            </Button>
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

export default EditAddressModal
