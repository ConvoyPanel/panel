import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import { FormikProvider, useFormik } from 'formik'
import { ServerContext } from '@/state/server'
import { AdminServerContext } from '@/state/admin/server'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import UsersSelectFormik from '@/components/admin/servers/UsersSelectFormik'
import NodesSelectFormik from '@/components/admin/servers/NodesSelectFormik'
import TextInputFormik from '@/components/elements/forms/TextInputFormik'
import SelectFormik from '@/components/elements/forms/SelectFormik'
import Button from '@/components/elements/Button'
import { useFlashKey } from '@/util/useFlash'
import updateServer from '@/api/admin/servers/updateServer'
import { EloquentStatus } from '@/api/server/types'

const GeneralContainer = () => {
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const setServer = AdminServerContext.useStoreActions(actions => actions.server.setServer)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:server:settings:general')

    const form = useFormik({
        initialValues: {
            name: server.name,
            hostname: server.hostname,
            vmid: server.vmid,
            userId: server.userId.toString(),
            nodeId: server.nodeId.toString(),
            status: server.status ?? 'ready',
        },
        onSubmit: async ({ status, nodeId, userId, ...values }, { setSubmitting }) => {
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
        <FormSection title='General Settings'>
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
                                <UsersSelectFormik />
                                <NodesSelectFormik />
                                <SelectFormik
                                    name={'status'}
                                    data={statusTypes}
                                    placeholder={'Status OK'}
                                    label={'Status'}
                                />
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
        </FormSection>
    )
}

export default GeneralContainer
