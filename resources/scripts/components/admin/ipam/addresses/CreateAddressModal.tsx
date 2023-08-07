import useAddressPoolSWR from '@/api/admin/addressPools/addresses/useAddressPoolSWR'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import RadioGroupForm from '@/components/elements/forms/RadioGroupForm'
import TextInputForm from '@/components/elements/forms/TextInputForm'
import Radio from '@/components/elements/inputs/Radio'
import { useFlashKey } from '@/util/useFlash'
import { ipAddress, macAddress } from '@/util/validation'
import { zodResolver } from '@hookform/resolvers/zod'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import ServersSelectForm from '@/components/admin/ipam/addresses/ServersSelectForm'
import createAddress from '@/api/admin/addressPools/addresses/createAddress'
import { KeyedMutator, mutate } from 'swr'
import { AddressResponse } from '@/api/admin/nodes/addresses/getAddresses'
import CheckboxForm from '@/components/elements/forms/CheckboxForm'
import { useMemo } from 'react'

interface Props {
    open: boolean
    onClose: () => void
    mutate: KeyedMutator<AddressResponse>
}

const calculateAddressBlockSize = (type: 'ipv4' | 'ipv6', cidr: number): number => {
    if (type === 'ipv4') {
        return Math.pow(2, 32 - cidr)
    } else {
        return 2 ** (128 - cidr)
    }
}

const CreateAddressModal = ({ open, onClose, mutate }: Props) => {
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.addressPools.addresses')
    const { data: pool } = useAddressPoolSWR()
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(`admin.addressPools.${pool.id}.addresses.create`)

    const schema = z.object({
        address: ipAddress().nonempty().max(191),
        type: z.enum(['ipv4', 'ipv6']),
        cidr: z.preprocess(Number, z.number().int().min(1).max(128)),
        isBulkAction: z.boolean(),
        gateway: ipAddress().nonempty().max(191),
        macAddress: macAddress().max(191).optional().or(z.literal('')),
        serverId: z.literal('').or(z.preprocess(Number, z.number())),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            address: '',
            type: 'ipv4',
            cidr: '',
            isBulkAction: false,
            gateway: '',
            macAddress: '',
            serverId: '',
        },
    })

    const watchType = form.watch('type')
    const watchIsBulkAction = form.watch('isBulkAction')
    const watchCidr = form.watch('cidr')

    const isBulkActionLabel = useMemo(() => {
        if (watchIsBulkAction) {
            const cidr = parseInt(watchCidr)
            if (!isNaN(cidr)) {
                return t('create_modal.bulk_action_checked', {
                    count: calculateAddressBlockSize(watchType as 'ipv4' | 'ipv6', cidr),
                })
            } else {
                return t('create_modal.bulk_action_checked', {
                    count: 0,
                })
            }
        } else {
            return t('create_modal.bulk_action_unchecked')
        }
    }, [watchType, watchIsBulkAction, watchCidr])

    const handleClose = () => {
        form.reset()
        onClose()
    }

    const submit = async (_data: any) => {
        const { macAddress, serverId, ...data } = _data as z.infer<typeof schema>

        clearFlashes()
        try {
            const address = await createAddress(pool.id, {
                macAddress: macAddress && macAddress.length > 0 ? macAddress : null,
                serverId: serverId !== '' ? serverId : null,
                include: ['server'],
                ...data,
            })

            mutate(data => {
                if (!data) return data
                if (data.pagination.currentPage !== 1) return data

                return {
                    ...data,
                    items: [address, ...data.items],
                }
            }, false)

            handleClose()
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>{t('create_address')}</Modal.Title>
            </Modal.Header>

            <FormProvider {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Modal.Body>
                        <FlashMessageRender className='mb-5' byKey={`admin.addressPools.${pool.id}.addresses.create`} />
                        <TextInputForm name='address' label={tStrings('address')} />
                        <RadioGroupForm name='type' orientation='vertical' spacing={6}>
                            <Radio name='type' value='ipv4' label={tStrings('ipv4')} />
                            <Radio name='type' value='ipv6' label={tStrings('ipv6')} />
                        </RadioGroupForm>
                        <TextInputForm name='cidr' label={tStrings('cidr')} placeholder='24' />
                        <CheckboxForm className='mt-3' name={'isBulkAction'} label={isBulkActionLabel} />
                        <TextInputForm name='gateway' label={tStrings('gateway')} />
                        <TextInputForm name='macAddress' label={tStrings('mac_address')} />
                        <ServersSelectForm />
                    </Modal.Body>

                    <Modal.Actions>
                        <Modal.Action type='button' onClick={handleClose}>
                            {tStrings('cancel')}
                        </Modal.Action>
                        <Modal.Action type='submit' loading={form.formState.isSubmitting}>
                            {tStrings('create')}
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormProvider>
        </Modal>
    )
}

export default CreateAddressModal
