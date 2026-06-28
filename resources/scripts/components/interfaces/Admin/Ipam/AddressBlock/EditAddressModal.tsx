import { PaginatedAddresses } from '@/types/address.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useParams } from '@tanstack/react-router'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { KeyedMutator } from '@/lib/swr'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import updateAddress from '@/api/admin/addressBlockGroups/addressBlocks/addresses/updateAddress.ts'

import ServerPicker from '@/components/interfaces/Admin/Ipam/AddressBlock/ServerPicker.tsx'
import { useAddressModal } from '@/components/interfaces/Admin/Ipam/AddressBlock/use-address-modal.ts'

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

const addressSchema = z.object({
    serverId: z.string().nullable().transform(val => val || null),
})

interface Props {
    mutate: KeyedMutator<PaginatedAddresses>
}

const EditAddressModal = ({ mutate }: Props) => {
    const { addressBlockGroupId, addressBlockId } = useParams({
        strict: false,
    }) as { addressBlockGroupId: string; addressBlockId: string }
    const [address, open, close] = useAddressModal(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'edit',
            state.closeModal,
        ])
    )

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

            close('edit')
            toast.success('Address updated')
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to save changes')
            throw e
        }
    }

    return (
        <Credenza open={open} onOpenChange={open => !open && close('edit')}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Editing Address {address?.ip}</CredenzaTitle>
                </CredenzaHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <CredenzaBody>
                            <ServerPicker
                                addressBlockGroupId={Number(
                                    addressBlockGroupId
                                )}
                            />
                            <Button
                                variant={'link'}
                                className={'px-0 block ml-auto'}
                                onClick={() => form.setValue('serverId', '')}
                                type="button"
                            >
                                Clear
                            </Button>
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

export default EditAddressModal
