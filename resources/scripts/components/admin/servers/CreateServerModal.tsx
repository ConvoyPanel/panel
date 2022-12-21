import FlashMessageRender from '@/components/elements/FlashMessageRenderer';
import Modal from '@/components/elements/Modal';
import { FormikProvider, useFormik } from 'formik';
import * as yup from 'yup'

interface Props {
    open: boolean
    onClose: () => void
}

const CreateServerModal = ({ open, onClose}: Props) => {
    const form = useFormik({
        initialValues: {
            name: '',
            nodeId: undefined as number | undefined,
            vmid: undefined as number | undefined,
            hostname: '',
            addressIds: [],
            cpu: undefined as number | undefined,
            memory: undefined as number | undefined,
            disk: undefined as number | undefined,
            backupsLimit: undefined as number | undefined,
            bandwidthLimit: undefined as number | undefined,
            createAfterSubmit: true,
        },
        validationSchema: yup.object({
            name: yup.string().max(40, 'Do not exceed 40 characters').required('A name is required.'),
            nodeId: yup.number().required('A node is required.'),
            vmid: yup.number(),
            hostname: yup.string().max(191, 'Do not exceed 191 characters'),
            addressIds: yup.array().of(yup.number()),
            cpu: yup.number().min(1, 'Can\'t have zero cpus lol').required('A CPU value is required.'),
            memory: yup.number().min(16, 'Please specify at least 16 MiB').required('A memory value is required.'),
            disk: yup.number().min(1, 'Can\'t have no disk lol').required('A disk value is required.'),
            backupsLimit: yup.number(),
            bandwidthLimit: yup.number(),
            createAfterSubmit: yup.boolean(),
        }),
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