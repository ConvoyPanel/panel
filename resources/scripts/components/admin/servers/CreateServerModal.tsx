import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import { FormikProvider, useFormik } from 'formik'
import * as yup from 'yup'
import { useFlashKey } from '@/util/useFlash'
import TextInputFormik from '@/components/elements/forms/TextInputFormik'
import { Text } from '@mantine/core'
import CheckboxFormik from '@/components/elements/forms/CheckboxFormik'
import NodesSelectFormik from '@/components/admin/servers/NodesSelectFormik'
import AddressesMultiSelectFormik from '@/components/admin/servers/AddressesMultiSelectFormik'

interface Props {
    nodeId?: number
    open: boolean
    onClose: () => void
}

const CreateServerModal = ({ nodeId, open, onClose }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:servers.create')

    const form = useFormik({
        initialValues: {
            name: '',
            nodeId: '',
            vmid: '',
            hostname: '',
            addressIds: [],
            cpu: '',
            memory: '',
            disk: '',
            backupsLimit: '',
            bandwidthLimit: '',
            createServer: true,
        },
        validationSchema: yup.object({
            name: yup.string().max(40, 'Do not exceed 40 characters').required('A name is required.'),
            nodeId: yup.number().required('A node is required.'),
            vmid: yup.number(),
            hostname: yup.string().max(191, 'Do not exceed 191 characters'),
            addressIds: yup.array().of(yup.number()),
            cpu: yup.number().min(1, "Can't have zero cpus lol").required('A CPU value is required.'),
            memory: yup.number().min(16, 'Please specify at least 16 MiB').required('A memory value is required.'),
            disk: yup.number().min(1, "Can't have no disk lol").required('A disk value is required.'),
            backupsLimit: yup.number(),
            bandwidthLimit: yup.number(),
            createServer: yup.boolean(),
        }),
        onSubmit: values => {},
    })

    const handleClose = () => {
        form.resetForm()
        onClose()
    }

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>Create a Server</Modal.Title>
            </Modal.Header>

            <FormikProvider value={form}>
                <form onSubmit={form.handleSubmit}>
                    <Modal.Body>
                        <FlashMessageRender className='mb-5' byKey={'admin:servers.create'} />
                        <TextInputFormik name={'name'} label={'Display Name'} />
                        <NodesSelectFormik />
                        <TextInputFormik name={'vmid'} label={'VMID'} placeholder={'Leave blank to generate'} />
                        <TextInputFormik name={'hostname'} label={'Hostname'} />
                        <AddressesMultiSelectFormik disabled={form.values.nodeId === ''} />
                        <TextInputFormik name={'cpu'} label={'CPUs'} />
                        <TextInputFormik name={'memory'} label={'Memory'} />
                        <TextInputFormik name={'disk'} label={'Disk'} />
                        <div className={'grid grid-cols-2 gap-3'}>
                            <TextInputFormik
                                name={'backupsLimit'}
                                label={'Backups Limit'}
                                placeholder={'Leave blank for no limit'}
                            />
                            <TextInputFormik
                                name={'bandwidthLimit'}
                                label={'Bandwidth Limit'}
                                placeholder={'Leave blank for no limit'}
                            />
                        </div>
                        <CheckboxFormik name={'createServer'} label={'Create Server'} className={'mt-3'} />
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
        </Modal>
    )
}

export default CreateServerModal
