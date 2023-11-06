import { AdminServerContext } from '@/state/admin/server'
import { useFlashKey } from '@/util/useFlash'
import { zodResolver } from '@hookform/resolvers/zod'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'

import updateBuild from '@/api/admin/servers/updateBuild'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import TextInputForm from '@/components/elements/forms/TextInputForm'

import AddressesMultiSelectForm from '@/components/admin/servers/AddressesMultiSelectForm'


const ServerBuildSettingsCard = () => {
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const setServer = AdminServerContext.useStoreActions(
        actions => actions.server.setServer
    )
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.servers.${server.uuid}.settings.hardware.build`
    )
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.servers.settings')
    const { t: tIndex } = useTranslation('admin.servers.index')

    const pluckedAddressIds = [
        ...server.limits.addresses.ipv4.map(address => address.id.toString()),
        ...server.limits.addresses.ipv6.map(address => address.id.toString()),
    ]

    const schema = z.object({
        cpu: z.preprocess(Number, z.number().min(1)),
        memory: z.preprocess(Number, z.number().min(16)),
        disk: z.preprocess(Number, z.number().min(1)),
        addressIds: z.array(z.preprocess(Number, z.number())),
        snapshotLimit: z.union([
            z.literal(''),
            z.preprocess(Number, z.number().min(0)),
        ]),
        backupLimit: z.union([
            z.literal(''),
            z.preprocess(Number, z.number().min(0)),
        ]),
        bandwidthLimit: z.union([
            z.literal(''),
            z.preprocess(Number, z.number().min(0)),
        ]),
        bandwidthUsage: z.preprocess(Number, z.number().min(0)),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            cpu: server.limits.cpu.toString(),
            memory: (server.limits.memory / 1048576).toString(),
            disk: (server.limits.disk / 1048576).toString(),
            addressIds: pluckedAddressIds,
            snapshotLimit: server.limits.snapshots?.toString() ?? '',
            backupLimit: server.limits.backups?.toString() ?? '',
            bandwidthLimit: server.limits.bandwidth
                ? (server.limits.bandwidth / 1048576).toString()
                : '',
            bandwidthUsage: (server.usages.bandwidth / 1048576).toString(),
        },
    })

    const submit = async (_data: any) => {
        const {
            memory,
            disk,
            snapshotLimit,
            backupLimit,
            bandwidthLimit,
            bandwidthUsage,
            ...data
        } = _data as z.infer<typeof schema>
        clearFlashes()

        try {
            const newServer = await updateBuild(server.uuid, {
                memory: memory * 1048576,
                disk: disk * 1048576,
                snapshotLimit: snapshotLimit !== '' ? snapshotLimit : null,
                backupLimit: backupLimit !== '' ? backupLimit : null,
                bandwidthLimit:
                    bandwidthLimit !== '' ? bandwidthLimit * 1048576 : null,
                bandwidthUsage: bandwidthUsage * 1048576,
                ...data,
            })

            setServer(newServer)

            form.reset({
                cpu: data.cpu.toString(),
                memory: memory.toString(),
                disk: disk.toString(),
                addressIds: data.addressIds.map(id => id.toString()),
                snapshotLimit: snapshotLimit.toString() ?? '',
                backupLimit: backupLimit.toString() ?? '',
                bandwidthLimit:
                    bandwidthLimit !== '' ? bandwidthLimit.toString() : '',
                bandwidthUsage: bandwidthUsage.toString(),
            })
        } catch (error) {
            clearAndAddHttpError(error as any)
        }
    }

    return (
        <FormCard className='w-full'>
            <FormProvider {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <FormCard.Body>
                        <FormCard.Title>{t('build.title')}</FormCard.Title>
                        <div className='space-y-3 mt-3'>
                            <FlashMessageRender
                                byKey={`admin.servers.${server.uuid}.settings.hardware.build`}
                            />
                            <TextInputForm name='cpu' label={tStrings('cpu')} />
                            <TextInputForm
                                name='memory'
                                label={`${tStrings('memory')} (MiB)`}
                            />
                            <TextInputForm
                                name='disk'
                                label={`${tStrings('disk')} (MiB)`}
                            />
                            <AddressesMultiSelectForm nodeId={server.nodeId} />
                            <TextInputForm
                                name='snapshotLimit'
                                label={tIndex('snapshot_limit')}
                                placeholder={'Leave blank for no limit'}
                            />
                            <TextInputForm
                                name='backupLimit'
                                label={tIndex('backup_limit')}
                                placeholder={'Leave blank for no limit'}
                            />
                            <TextInputForm
                                name='bandwidthLimit'
                                label={`${tIndex('bandwidth_limit')} (MiB)`}
                                placeholder={
                                    tIndex('limit_placeholder') ??
                                    'Leave blank for no limit'
                                }
                            />
                            <TextInputForm
                                name='bandwidthUsage'
                                label={`${tIndex('bandwidth_usage')} (MiB)`}
                            />
                        </div>
                    </FormCard.Body>
                    <FormCard.Footer>
                        <Button
                            loading={form.formState.isSubmitting}
                            disabled={!form.formState.isDirty}
                            type='submit'
                            variant='filled'
                            color='success'
                            size='sm'
                        >
                            {tStrings('save')}
                        </Button>
                    </FormCard.Footer>
                </form>
            </FormProvider>
        </FormCard>
    )
}

export default ServerBuildSettingsCard