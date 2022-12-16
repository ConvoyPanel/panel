import TextInputFormik from '@/components/elements/forms/TextInputFormik'
import Modal from '@/components/elements/Modal'
import useFlash from '@/util/useFlash'
import { FormikProvider, useFormik } from 'formik'
import * as yup from 'yup'

interface Props {
    open: boolean
    onClose: () => void
}

const NewNodeModal = ({ open, onClose }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash()

    const form = useFormik({
        initialValues: {
            locationId: undefined as number | undefined,
            name: '',
            cluster: '',
            tokenId: '',
            secret: '',
            fqdn: '',
            port: 8006,
            memory: 0,
            memoryOverallocate: 0,
            disk: 0,
            diskOverallocate: 0,
            vmStorage: '',
            backupStorage: '',
            network: '',
        },
        validationSchema: yup.object({
            locationId: yup.number().required('Specify a location'),
            name: yup.string().required('Specify a name').max(191, 'Please limit up to 191 characters'),
            cluster: yup.string().required('Specify a cluster').max(191, 'Please limit up to 191 characters'),
            tokenId: yup.string().required('Specify a token ID').max(191, 'Please limit up to 191 characters'),
            secret: yup.string().required('Specify a secret').max(191, 'Please limit up to 191 characters'),
            fqdn: yup
                .string()
                .matches(
                    /((https?):\/\/)?(www.)?[a-z0-9]+(\.[a-z]{2,}){1,3}(#?\/?[a-zA-Z0-9#]+)*\/?(\?[a-zA-Z0-9-_]+=[a-zA-Z0-9-%]+&?)?$/,
                    'Enter a valid FQDN'
                )
                .required('Specify a FQDN')
                .max(191, 'Please limit up to 191 characters'),
            port: yup
                .number()
                .required('Specify a port')
                .min(1, 'Please specify a valid port')
                .max(65535, 'Please specify a valid port'),
            memory: yup.number().required('Specify a memory').min(0, 'Please specify a valid memory'),
            memoryOverallocate: yup
                .number()
                .required('Specify a memory overallocate')
                .min(0, 'Please specify a valid memory overallocate'),
            disk: yup.number().required('Specify a disk').min(0, 'Please specify a valid disk'),
            diskOverallocate: yup
                .number()
                .required('Specify a disk overallocate')
                .min(0, 'Please specify a valid disk overallocate'),
            vmStorage: yup.string().required('Specify a VM storage').max(191, 'Please limit up to 191 characters'),
            backupStorage: yup
                .string()
                .required('Specify a backup storage')
                .max(191, 'Please limit up to 191 characters'),
            network: yup.string().required('Specify a network').max(191, 'Please limit up to 191 characters'),
        }),
        onSubmit: (values, { setSubmitting }) => {},
    })

    const handleClose = () => {
        form.resetForm()
        onClose()
    }

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>New Node</Modal.Title>
            </Modal.Header>

            <FormikProvider value={form}>
                <Modal.Body>
                    <TextInputFormik name='name' label='Display Name' />
                    <TextInputFormik name='cluster' label='Cluster' />
                    <div className='grid gap-3 grid-cols-2'>
                        <TextInputFormik name='tokenId' label='Token ID' />
                        <TextInputFormik name='secret' label='Secret' />
                    </div>
                    <TextInputFormik name='fqdn' label='FQDN' />
                    <TextInputFormik name='port' label='Port' />
                    <div className='grid gap-3 grid-cols-2'>
                        <TextInputFormik name='memory' label='Memory (MiB)' />
                        <TextInputFormik name='memoryOverallocate' label='Memory Overallocate' />
                    </div>
                    <div className='grid gap-3 grid-cols-2'>
                        <TextInputFormik name='disk' label='Disk (MiB)' />
                        <TextInputFormik name='diskOverallocate' label='Disk Overallocate' />
                    </div>
                    <div className='grid gap-3 grid-cols-3'>
                        <TextInputFormik name='vmStorage' label='VM Storage' placeholder='local' />
                        <TextInputFormik name='backupStorage' label='Backup Storage' placeholder='local' />
                        <TextInputFormik name='network' label='Network' placeholder='vmbr0' />
                    </div>
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action type='button' onClick={handleClose}>
                        Cancel
                    </Modal.Action>
                    <Modal.Action type='submit' loading={form.isSubmitting}>
                        Create
                    </Modal.Action>
                </Modal.Actions>
            </FormikProvider>
        </Modal>
    )
}

export default NewNodeModal
