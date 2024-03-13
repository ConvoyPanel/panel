import { useFlashKey } from '@/util/useFlash'
import usePagination from '@/util/usePagination'
import { FormikProvider, useFormik } from 'formik'

import deleteAddress from '@/api/admin/addressPools/addresses/deleteAddress'
import useAddressesSWR from '@/api/admin/nodes/addresses/useAddressesSWR'
import useNodeSWR from '@/api/admin/nodes/useNodeSWR'
import { Address } from '@/api/server/getServer'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'


interface Props {
    open: boolean
    onClose: () => void
    address: Address
}

const DeleteAddressModal = ({open, onClose, address}: Props) => {
    const {data: node} = useNodeSWR()
    const {clearFlashes, clearAndAddHttpError} = useFlashKey(
            `admin.nodes.${node.id}.addresses.${address.id}.delete`
    )
    const [page] = usePagination()
    const {mutate} = useAddressesSWR(node.id, {page, include: ['server']})

    const form = useFormik({
        initialValues: {
            syncNetworkConfig: true,
        },
        onSubmit: async ({syncNetworkConfig}, {setSubmitting}) => {
            clearFlashes()
            setSubmitting(true)
            try {
                await deleteAddress(address.addressPoolId, address.id)

                mutate(data => {
                    if (!data) return data

                    return {
                        ...data,
                        items: data.items.filter(a => a.id !== address.id),
                    }
                }, false)
                onClose()
            } catch (error) {
                clearAndAddHttpError(error as Error)
            }
            setSubmitting(false)
        },
    })

    return (
            <Modal open={open} onClose={onClose}>
                <Modal.Header>
                    <Modal.Title>Delete Address</Modal.Title>
                </Modal.Header>
                <FormikProvider value={form}>
                    <form onSubmit={form.handleSubmit}>
                        <Modal.Body>
                            <FlashMessageRender
                                    className='mb-5'
                                    byKey={`admin.nodes.${node.id}.addresses.${address.id}.delete`}
                            />

                            <Modal.Description>
                                Are you sure you want to delete this address? This
                                action cannot be undone.
                            </Modal.Description>
                        </Modal.Body>
                        <Modal.Actions>
                            <Modal.Action type='button' onClick={onClose}>
                                Cancel
                            </Modal.Action>
                            <Modal.Action type='submit' loading={form.isSubmitting}>
                                Delete
                            </Modal.Action>
                        </Modal.Actions>
                    </form>
                </FormikProvider>
            </Modal>
    )
}

export default DeleteAddressModal