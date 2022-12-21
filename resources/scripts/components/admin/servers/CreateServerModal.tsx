import FlashMessageRender from '@/components/elements/FlashMessageRenderer';
import Modal from '@/components/elements/Modal';
import { FormikProvider, useFormik } from 'formik';

interface Props {
    open: boolean
    onClose: () => void
}

const CreateServerModal = ({ open, onClose}: Props) => {
    const form = useFormik({
        initialValues: {
            name: '',
            cpu: undefined,
            memory: undefined,
            disk: undefined,
        },
        onSubmit: (values) => {

        }
    })

    const handleClose = () => {
        form.resetForm()
        onClose()
    }

    return (<Modal open={open} onClose={handleClose}>
        <Modal.Header>
            <Modal.Title>Create a Node</Modal.Title>
        </Modal.Header>

        <FormikProvider value={form}>
            <form onSubmit={form.handleSubmit}>
                <Modal.Body>
                    <FlashMessageRender className='mb-5' byKey={'admin:servers:create'} />
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action type='button' onClick={handleClose}>
                        Cancel
                    </Modal.Action>
                    <Modal.Action type='submit' loading={form.isSubmitting}>
                        Create
                    </Modal.Action>
                </Modal.Actions>
            </form>
        </FormikProvider>
    </Modal>);
}

export default CreateServerModal;