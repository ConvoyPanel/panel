import useAddressesSWR from '@/api/admin/nodes/addresses/useAddressesSWR'
import { Address } from '@/api/server/getServer'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import CheckboxFormik from '@/components/elements/forms/CheckboxFormik'
import TextInputFormik from '@/components/elements/forms/TextInputFormik'
import Modal from '@/components/elements/Modal'
import { NodeContext } from '@/state/admin/node'
import useFlash from '@/util/useFlash'
import { FormikProvider, useFormik } from 'formik'
import Radio from '@/components/elements/inputs/Radio'
import RadioGroupFormik from '@/components/elements/forms/RadioGroupFormik'
import ServersSelectFormik from '@/components/admin/nodes/addresses/ServersSelectFormik'
import * as yup from 'yup'
import createAddress from '@/api/admin/nodes/addresses/createAddress'
import updateAddress from '@/api/admin/nodes/addresses/updateAddress'
import usePagination from '@/util/usePagination'

interface Props {
    open: boolean
    onClose: () => void
    address?: Address
}

const EditAddressModal = ({ open, onClose, address }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const [page] = usePagination()
    const { mutate } = useAddressesSWR(nodeId, {page, includes: ['server'] })

    const form = useFormik({
        enableReinitialize: true,
        initialValues: {
            serverId: address?.server!.internalId ?? (undefined as number | undefined),
            address: address?.address ?? '',
            cidr: address?.cidr as number | undefined,
            gateway: address?.gateway ?? '',
            macAddress: address?.macAddress ?? '',
            type: address?.type ?? 'ipv4',
            syncNetworkConfig: true,
        },
        validationSchema: yup.object({
            serverId: yup.number().optional(),
            address: yup.string().required('Specify an address'),
            cidr: yup.number().required('Specify a CIDR'),
            gateway: yup.string().required('Specify a gateway'),
            macAddress: yup.string().optional(),
            type: yup.string().required('Specify a type'),
        }),
        onSubmit: async ({cidr, ...values}, { setSubmitting }) => {
            clearFlashes('admin:node:addresses.edit')
            setSubmitting(true)
            try {
                if (address) {
                    const updatedAddress = await updateAddress(nodeId, address.id, {
                        ...values,
                        cidr: cidr as number,
                    })

                    mutate(data => {
                        if (!data) return data

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
                } else {
                    const address = await createAddress(nodeId, {
                        ...values,
                        cidr: cidr as number,
                    })

                    mutate(data => {
                        if (!data) return data

                        return {
                            ...data,
                            items: [ address, ...data.items ],
                        }
                    }, false)
                }

                handleClose()
            } catch (error) {
                clearAndAddHttpError({ key: 'admin:node:addresses.edit', error })
            }

            setSubmitting(false)
        },
    })

    const handleClose = () => {
        form.resetForm()
        onClose()
    }

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>{address ? 'Edit' : 'New'} Address</Modal.Title>
            </Modal.Header>

            <FormikProvider value={form}>
                <form onSubmit={form.handleSubmit}>
                    <Modal.Body>
                        <FlashMessageRender className='mb-5' byKey={'admin:node:addresses.edit'} />
                        <TextInputFormik name='address' label='Address' placeholder='127.0.0.1' />
                        <RadioGroupFormik name='type' orientation='vertical' spacing={6}>
                            <Radio value='ipv4' label='IPv4' />
                            <Radio value='ipv6' label='IPv6' />
                        </RadioGroupFormik>
                        <TextInputFormik name='cidr' label='Cidr/Subnet mask' placeholder='24' />
                        <TextInputFormik name='gateway' label='Gateway' />
                        <TextInputFormik name='macAddress' label='Mac Address (optional)' />
                        <ServersSelectFormik />
                        <CheckboxFormik name='syncNetworkConfig' label='Sync Network Configuration' className='mt-3' />
                    </Modal.Body>
                    <Modal.Actions>
                        <Modal.Action type='button' onClick={handleClose}>
                            Cancel
                        </Modal.Action>
                        <Modal.Action type='submit' loading={form.isSubmitting}>
                            {address ? 'Update' : 'Create'}
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormikProvider>
        </Modal>
    )
}

export default EditAddressModal
