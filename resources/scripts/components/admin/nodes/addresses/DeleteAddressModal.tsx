import useAddressesSWR from '@/api/admin/nodes/addresses/useAddressesSWR'
import { Address } from '@/api/server/getServer'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import { NodeContext } from '@/state/admin/node'
import useFlash from '@/util/useFlash'
import { useState } from 'react'
import deleteAddress from '@/api/admin/nodes/addresses/deleteAddress'
import { FormikProvider, useFormik } from 'formik'
import CheckboxFormik from '@/components/elements/forms/CheckboxFormik'
import usePagination from '@/util/usePagination'

interface Props {
    open: boolean
    onClose: () => void
    address: Address
}

const DeleteAddressModal = ({ open, onClose, address }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const [page] = usePagination()
    const { mutate } = useAddressesSWR(nodeId, {page, includes: ['server'] })

    const form = useFormik({
        initialValues: {
            syncNetworkConfig: true,
        },
        onSubmit: async ({ syncNetworkConfig }, { setSubmitting }) => {
            clearFlashes('admin:node:addresses.delete')
            setSubmitting(true)
            try {
                await deleteAddress(nodeId, address.id, syncNetworkConfig)

                mutate(data => {
                    if (!data) return data

                    return {
                        ...data,
                        items: data.items.filter(a => a.id !== address.id),
                    }
                }, false)
                onClose()
            } catch (error) {
                clearAndAddHttpError({ key: 'admin:node:addresses.delete', error })
            }
            setSubmitting(false)
        },
    })

    return (
        <Modal open={open} onClose={onClose}>
            <Modal.Header>
                <Modal.Title>{address ? 'Edit' : 'New'} Address</Modal.Title>
            </Modal.Header>
            <FormikProvider value={form}>
                <form onSubmit={form.handleSubmit}>
                    <Modal.Body>
                        <FlashMessageRender className='mb-5' byKey={'admin:node:addresses.delete'} />
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
