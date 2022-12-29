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

interface Props {
    open: boolean
    onClose: () => void
    address?: Address
}

const EditAddressModal = ({ open, onClose, address }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const { mutate } = useAddressesSWR(nodeId, {})

    const form = useFormik({
        enableReinitialize: true,
        initialValues: {
            serverId: address?.server!.internalId ?? (undefined as number | undefined),
            address: address?.address ?? '',
            cidr: address?.cidr as number | undefined,
            gateway: address?.gateway ?? '',
            macAddress: address?.macAddress ?? '',
            type: address?.type ?? '',
        },
        onSubmit: async (values, { setSubmitting }) => {},
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
                        <RadioGroupFormik name='favoriteFramework' orientation='vertical' spacing={6}>
                            <Radio value='ipv4' label='IPv4' />
                            <Radio value='ipv6' label='IPv6' />
                        </RadioGroupFormik>
                        <TextInputFormik name='cidr' label='Cidr/Subnet mask' placeholder='24' />
                        <TextInputFormik name='gateway' label='Gateway' />
                        <TextInputFormik name='macAddress' label='Mac Address (optional)' />
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
