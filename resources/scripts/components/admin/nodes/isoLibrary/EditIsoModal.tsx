import { ISO, IsoResponse } from '@/api/admin/nodes/isos/getIsos'
import updateIso from '@/api/admin/nodes/isos/updateIso'
import useIsosSWR from '@/api/admin/nodes/isos/useIsosSWR'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import CheckboxFormik from '@/components/elements/forms/CheckboxFormik'
import TextInputFormik from '@/components/elements/forms/TextInputFormik'
import Modal from '@/components/elements/Modal'
import { NodeContext } from '@/state/admin/node'
import useFlash from '@/util/useFlash'
import { FormikProvider, useFormik } from 'formik'
import * as yup from 'yup'

interface Props {
    open: boolean
    onClose: () => void
    iso: ISO
}

const EditIsoModal = ({ open, onClose, iso }: Props) => {
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const { mutate } = useIsosSWR({ nodeId })

    const form = useFormik({
        enableReinitialize: true,
        initialValues: {
            name: iso.name,
            hidden: iso.hidden,
        },
        validationSchema: yup.object().shape({
            name: yup.string().required('Name is required.'),
            hidden: yup.boolean(),
        }),
        onSubmit: ({ name, hidden }, { setSubmitting }) => {
            setSubmitting(true)
            clearFlashes('admin:node:iso.update')
            updateIso(nodeId, iso.uuid, name, hidden)
                .then(newIso => {
                    mutate(
                        mutateData =>
                            ({
                                ...mutateData,
                                items: mutateData!.items.map(item => (item.uuid === newIso.uuid ? newIso : item)),
                            } as IsoResponse),
                        false
                    )
                    setSubmitting(false)
                    onClose()
                })
                .catch(error => {
                    clearAndAddHttpError({ key: 'admin:node:iso.update', error })
                    setSubmitting(false)
                })
        },
    })

    const handleClose = () => {
        form.resetForm()
        onClose()
    }

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>Update ISO</Modal.Title>
            </Modal.Header>

            <FormikProvider value={form}>
                <form onSubmit={form.handleSubmit}>
                    <Modal.Body>
                        <FlashMessageRender className='mb-5' byKey={'admin:node:iso.update'} />
                        <TextInputFormik name='name' label='Display Name' />
                        <CheckboxFormik className='mt-3' name='hidden' label='Hidden' />
                    </Modal.Body>

                    <Modal.Actions>
                        <Modal.Action type='button' onClick={handleClose}>
                            Cancel
                        </Modal.Action>
                        <Modal.Action type='submit' loading={form.isSubmitting}>
                            Update
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormikProvider>
        </Modal>
    )
}

export default EditIsoModal
