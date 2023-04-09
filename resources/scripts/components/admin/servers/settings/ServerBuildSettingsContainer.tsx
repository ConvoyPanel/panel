import FormCard from '@/components/elements/FormCard'
import { FormikProvider, useFormik } from 'formik'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import TextInputFormik from '@/components/elements/formik/TextInputFormik'
import UsersSelectFormik from '@/components/admin/servers/UsersSelectFormik'
import NodesSelectFormik from '@/components/admin/servers/NodesSelectFormik'
import SelectFormik from '@/components/elements/formik/SelectFormik'
import Button from '@/components/elements/Button'
import FormSection from '@/components/elements/FormSection'
import { AdminServerContext } from '@/state/admin/server'
import { useFlashKey } from '@/util/useFlash'
import updateServer from '@/api/admin/servers/updateServer'
import { EloquentStatus } from '@/api/server/types'
import updateBuild from '@/api/admin/servers/updateBuild'
import * as yup from 'yup'
import { useMemo } from 'react'
import AddressesMultiSelectFormik from '@/components/admin/servers/AddressesMultiSelectFormik'

const ServerBuildSettingsContainer = () => {
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const setServer = AdminServerContext.useStoreActions(actions => actions.server.setServer)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:server:settings:build')

    const pluckedAddressIds = useMemo(() => {
        return [
            ...server.limits.addresses.ipv4.map(address => address.id.toString()),
            ...server.limits.addresses.ipv6.map(address => address.id.toString()),
        ]
    }, [server])

    const form = useFormik({
        enableReinitialize: true,
        initialValues: {
            cpu: server.limits.cpu.toString(),
            memory: (server.limits.memory / 1048576).toString(),
            disk: (server.limits.disk / 1048576).toString(),
            addressIds: pluckedAddressIds,
            snapshotLimit: server.limits.snapshots?.toString() ?? '',
            backupLimit: server.limits.backups?.toString() ?? '',
            bandwidthLimit: server.limits.bandwidth ? (server.limits.bandwidth / 1048576).toString() : '',
            bandwidthUsage: (server.usages.bandwidth / 1048576).toString(),
        },
        validationSchema: yup.object({
            cpu: yup.number().min(1, "Can't have zero cpus lol").required('A CPU value is required.'),
            memory: yup.number().min(16, 'Please specify at least 16 MiB').required('A memory value is required.'),
            disk: yup.number().min(1, "Can't have no disk lol").required('A disk value is required.'),
            snapshotLimit: yup.number().min(0),
            backupLimit: yup.number().min(0),
            bandwidthLimit: yup.number().min(0),
            bandwidthUsage: yup.number().min(0),
        }),
        onSubmit: async ({
            addressIds,
            snapshotLimit,
            backupLimit,
            bandwidthLimit,
            bandwidthUsage,
            memory,
            disk,
            cpu,
        }) => {
            clearFlashes()
            try {
                const newServer = await updateBuild(server.uuid, {
                    cpu: parseInt(cpu),
                    memory: parseInt(memory) * 1048576,
                    disk: parseInt(disk) * 1048576,
                    addressIds: addressIds.map(id => parseInt(id)),
                    snapshotLimit: snapshotLimit !== '' ? parseInt(snapshotLimit) : null,
                    backupLimit: backupLimit !== '' ? parseInt(backupLimit) : null,
                    bandwidthLimit: bandwidthLimit !== '' ? parseInt(bandwidthLimit) * 1048576 : null,
                    bandwidthUsage: parseInt(bandwidthUsage) * 1048576,
                })

                setServer(newServer)
            } catch (error) {
                clearAndAddHttpError(error as any)
            }
        },
    })

    return (
        <FormCard className='w-full'>
            <FormikProvider value={form}>
                <form onSubmit={form.handleSubmit}>
                    <FormCard.Body>
                        <FormCard.Title>Server Build</FormCard.Title>
                        <div className='space-y-3 mt-3'>
                            <FlashMessageRender byKey='admin:server:settings:build' />
                            <TextInputFormik name='cpu' label='CPU' />
                            <TextInputFormik name='memory' label='Memory (MiB)' />
                            <TextInputFormik name='disk' label='Disk (MiB)' />
                            <AddressesMultiSelectFormik nodeId={server.nodeId} />
                            <TextInputFormik
                                name='snapshotLimit'
                                label='Snapshots Limit'
                                placeholder={'Leave blank for no limit'}
                            />
                            <TextInputFormik
                                name='backupLimit'
                                label='Backups Limit'
                                placeholder={'Leave blank for no limit'}
                            />
                            <TextInputFormik
                                name='bandwidthLimit'
                                label='Bandwidth Limit (MiB)'
                                placeholder={'Leave blank for no limit'}
                            />
                            <TextInputFormik name='bandwidthUsage' label='Bandwidth Usage (MiB)' />
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

export default ServerBuildSettingsContainer
