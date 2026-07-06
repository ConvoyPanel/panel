import { PaginatedAddressBlocks } from '@/types/address-block.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { Mutator } from '@/types/query.ts'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import { addressBlockSchema } from '@/api/admin/addressBlockGroups/addressBlocks/createAddressBlock.ts'
import updateAddressBlock from '@/api/admin/addressBlockGroups/addressBlocks/updateAddressBlock.ts'

import { useAddressBlockModal } from '@/components/interfaces/Admin/Ipam/AddressBlock/use-address-block-modal.ts'

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
import { InputForm, TextareaForm } from '@/components/ui/Forms'

interface Props {
    addressBlockGroupId: number
    mutate: Mutator<PaginatedAddressBlocks>
}

const EditAddressBlockModal = ({ addressBlockGroupId, mutate }: Props) => {
    const [addressBlock, open, close] = useAddressBlockModal(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'edit',
            state.closeModal,
        ])
    )

    const form = useForm({
        resolver: zodResolver(addressBlockSchema),
        defaultValues: {
            name: '',
            description: '',
            version: addressBlock?.version,
            baseIp: '',
            gateway: '',
            macAddress: '',
            prefixLengthFrom: 0,
            prefixLengthTo: 0,
        },
    })

    useEffect(() => {
        if (!addressBlock) return

        form.reset({
            name: addressBlock.name || '',
            description: addressBlock.description || '',
            version: addressBlock.version,
            baseIp: addressBlock.baseIp,
            gateway: addressBlock.gateway || '',
            macAddress: addressBlock.macAddress || '',
            prefixLengthFrom: addressBlock.prefixLengthFrom,
            prefixLengthTo: addressBlock.prefixLengthTo,
        })
    }, [addressBlock])

    const submit = async (data: z.infer<typeof addressBlockSchema>) => {
        try {
            if (!addressBlock) return

            const updatedAddressBlock = await updateAddressBlock(
                addressBlockGroupId,
                addressBlock.id,
                data
            )

            await mutate(data => {
                if (!data) return

                return {
                    ...data,
                    items: data.items.map(item => {
                        if (item.id === updatedAddressBlock.id) {
                            return updatedAddressBlock
                        }
                        return item
                    }),
                }
            }, false)

            close('edit')
            toast.success('Address block updated')
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
                    <CredenzaTitle>
                        Editing {addressBlock?.name || 'Address Block'}
                    </CredenzaTitle>
                </CredenzaHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <CredenzaBody className={'space-y-2'}>
                            <InputForm name={'name'} label={'Name'} />
                            <TextareaForm
                                name={'description'}
                                label={'Description'}
                            />
                            <InputForm name={'baseIp'} label={'Base IP'} />
                            <InputForm name={'gateway'} label={'Gateway'} />
                            <InputForm
                                name={'macAddress'}
                                label={'MAC Address'}
                            />
                            <div className={'grid grid-cols-2 gap-2'}>
                                <InputForm
                                    name={'prefixLengthFrom'}
                                    label={'Source Prefix Length'}
                                    type={'number'}
                                    min={0}
                                    max={128}
                                />
                                <InputForm
                                    name={'prefixLengthTo'}
                                    label={'Output Prefix Length'}
                                    type={'number'}
                                    min={0}
                                    max={128}
                                />
                            </div>
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

export default EditAddressBlockModal
