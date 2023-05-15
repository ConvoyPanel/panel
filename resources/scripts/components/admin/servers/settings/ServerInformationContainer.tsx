import { FormikProvider, useFormik } from 'formik'
import FormCard from '@/components/elements/FormCard'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import TextInputFormik from '@/components/elements/formik/TextInputFormik'
import UsersSelectForm from '@/components/admin/servers/UsersSelectForm'
import NodesSelectForm from '@/components/admin/servers/NodesSelectForm'
import SelectFormik from '@/components/elements/formik/SelectFormik'
import Button from '@/components/elements/Button'
import { AdminServerContext } from '@/state/admin/server'
import { useFlashKey } from '@/util/useFlash'
import updateServer from '@/api/admin/servers/updateServer'
import { EloquentStatus } from '@/api/server/types'
import * as yup from 'yup'

const ServerInformationContainer = () => {
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const setServer = AdminServerContext.useStoreActions(actions => actions.server.setServer)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:server:settings:general')

    const form = useFormik({
        enableReinitialize: true,
        initialValues: {
            name: server.name,
            hostname: server.hostname,
            vmid: server.vmid,
            userId: server.userId.toString(),
            nodeId: server.nodeId.toString(),
            status: server.status ?? 'ready',
        },
        validationSchema: yup.object({
            name: yup.string().required('A name is required').max(40),
            hostname: yup
                .string()
                .required()
                .max(191)
                // @ts-ignore
                .hostname(),
            vmid: yup.number().min(100).max(999999999),
            userId: yup.number().required(),
            nodeId: yup.number().required(),
        }),
        onSubmit: async ({ status, nodeId, userId, ...values }) => {
            clearFlashes()
            try {
                await updateServer(server.uuid, {
                    status: status === 'ready' ? null : (status as EloquentStatus),
                    nodeId: parseInt(nodeId),
                    userId: parseInt(userId),
                    ...values,
                })

                setServer({
                    ...server,
                    nodeId: parseInt(nodeId),
                    userId: parseInt(userId),
                    status: status === 'ready' ? null : (status as EloquentStatus),
                    ...values,
                })
            } catch (error) {
                clearAndAddHttpError(error as any)
            }
        },
    })

    const statusTypes = [
        { value: 'ready', label: 'Ready' },
        { value: 'installing', label: 'Installation In Progress' },
        { value: 'install_failed', label: 'Recent Installation Failed' },
        { value: 'suspended', label: 'Suspended' },
        { value: 'restoring_backup', label: 'Restoring From a Backup' },
        { value: 'restoring_snapshot', label: 'Restoring From a Snapshot' },
    ]

    return (
        <FormCard className='w-full'>
            <FormikProvider value={form}>
                <form onSubmit={form.handleSubmit}>
                    <FormCard.Body>
                        <FormCard.Title>Server Information</FormCard.Title>
                        <div className='space-y-3 mt-3'>
                            <FlashMessageRender byKey='admin:server:settings:general' />
                            <TextInputFormik name='name' label='Display Name' />
                            <TextInputFormik name='hostname' label='Hostname' />
                            <TextInputFormik name='vmid' label='VMID' />
                            <UsersSelectForm />
                            <NodesSelectForm />
                            <SelectFormik name={'status'} data={statusTypes} label={'Status'} />
                        </div>
                    </FormCard.Body>
                    <FormCard.Footer>
                        <Button
                            loading={form.isSubmitting}
                            disabled={!form.dirty}
                            type='submit'
                            variant='filled'
                            color='success'
                            size='sm'
                        >
                            Save
                        </Button>
                    </FormCard.Footer>
                </form>
            </FormikProvider>
        </FormCard>
    )
}

export default ServerInformationContainer
