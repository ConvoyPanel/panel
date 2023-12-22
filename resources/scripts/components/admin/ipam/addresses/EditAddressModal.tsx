import { useFlashKey } from '@/util/useFlash'
import { ipAddress, macAddress } from '@/util/validation'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { KeyedMutator } from 'swr'
import { z } from 'zod'

import updateAddress from '@/api/admin/addressPools/addresses/updateAddress'
import { AddressResponse } from '@/api/admin/nodes/addresses/getAddresses'
import { Address } from '@/api/server/getServer'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import RadioGroupForm from '@/components/elements/forms/RadioGroupForm'
import TextInputForm from '@/components/elements/forms/TextInputForm'
import Radio from '@/components/elements/inputs/Radio'

import ServersSelectForm from '@/components/admin/ipam/addresses/ServersSelectForm'


interface Props {
    address: Address | null
    onClose: () => void
    mutate: KeyedMutator<AddressResponse>
}

const EditAddressModal = ({ address, onClose, mutate }: Props) => {
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.addressPools.addresses')
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.addressPools.${address?.addressPoolId}.addresses.${address?.id}.edit`
    )

    const schema = z.object({
        address: ipAddress().max(191),
        type: z.enum(['ipv4', 'ipv6']),
        cidr: z.preprocess(Number, z.number().int().min(1).max(128)),
        gateway: ipAddress().max(191),
        macAddress: macAddress().max(191).optional().or(z.literal('')),
        serverId: z
            .literal('')
            .or(z.null())
            .or(z.preprocess(Number, z.number())),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            address: '',
            type: 'ipv4',
            cidr: '',
            gateway: '',
            macAddress: '',
            serverId: '',
        },
    })

    useEffect(() => {
        form.reset({
            address: address?.address ?? '',
            type: address?.type ?? 'ipv4',
            cidr: address?.cidr.toString() ?? '',
            gateway: address?.gateway ?? '',
            macAddress: address?.macAddress ?? '',
            serverId: address?.serverId?.toString() ?? '',
        })
    }, [address])

    const handleClose = () => {
        form.reset()
        onClose()
    }

    const submit = async (_data: any) => {
        const { macAddress, serverId, ...data } = _data as z.infer<
            typeof schema
        >

        clearFlashes()
        try {
            const updatedAddress = await updateAddress(
                address!.addressPoolId,
                address!.id,
                {
                    macAddress:
                        macAddress && macAddress.length > 0 ? macAddress : null,
                    serverId: serverId !== '' ? serverId : null,
                    include: ['server'],
                    ...data,
                }
            )

            mutate(data => {
                if (!data) return data

                return {
                    pagination: data.pagination,
                    items: data.items.map(item =>
                        item.id === updatedAddress.id ? updatedAddress : item
                    ),
                }
            }, false)

            handleClose()
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    return (
        <Modal open={Boolean(address)} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>{t('edit_modal.title')}</Modal.Title>
            </Modal.Header>

            <FormProvider {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Modal.Body>
                        <FlashMessageRender
                            className='mb-5'
                            byKey={`admin.addressPools.${address?.addressPoolId}.addresses.${address?.id}.edit`}
                        />
                        <TextInputForm
                            name='address'
                            label={tStrings('address_one')}
                        />
                        <RadioGroupForm
                            name='type'
                            orientation='vertical'
                            spacing={6}
                        >
                            <Radio
                                name='type'
                                value='ipv4'
                                label={tStrings('ipv4')}
                            />
                            <Radio
                                name='type'
                                value='ipv6'
                                label={tStrings('ipv6')}
                            />
                        </RadioGroupForm>
                        <TextInputForm
                            name='cidr'
                            label={tStrings('cidr')}
                            placeholder='24'
                        />
                        <TextInputForm
                            name='gateway'
                            label={tStrings('gateway')}
                        />
                        <TextInputForm
                            name='macAddress'
                            label={tStrings('mac_address')}
                        />
                        <ServersSelectForm
                            addressPoolId={address?.addressPoolId}
                        />
                    </Modal.Body>

                    <Modal.Actions>
                        <Modal.Action type='button' onClick={handleClose}>
                            {tStrings('cancel')}
                        </Modal.Action>
                        <Modal.Action
                            type='submit'
                            loading={form.formState.isSubmitting}
                        >
                            {tStrings('save')}
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormProvider>
        </Modal>
    )
}

export default EditAddressModal