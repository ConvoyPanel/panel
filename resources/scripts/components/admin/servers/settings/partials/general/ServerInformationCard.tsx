import { AdminServerContext } from '@/state/admin/server'
import { useFlashKey } from '@/util/useFlash'
import { hostname } from '@/util/validation'
import { zodResolver } from '@hookform/resolvers/zod'
import React from 'react'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'

import updateServer from '@/api/admin/servers/updateServer'
import { EloquentStatus } from '@/api/server/types'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import SelectForm from '@/components/elements/forms/SelectForm'
import TextInputForm from '@/components/elements/forms/TextInputForm'

import NodesSelectForm from '@/components/admin/servers/NodesSelectForm'
import UsersSelectForm from '@/components/admin/servers/UsersSelectForm'

const ServerInformationCard = () => {
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const setServer = AdminServerContext.useStoreActions(
        actions => actions.server.setServer
    )
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.servers.${server.uuid}.settings.general.info`
    )
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.servers.settings')

    const schema = z.object({
        name: z.string().max(60).min(1),
        hostname: hostname().max(191).min(1),
        vmid: z.preprocess(Number, z.number().min(100).max(999999999)),
        userId: z.preprocess(Number, z.number()),
        status: z.string(),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: server.name,
            hostname: server.hostname,
            vmid: server.vmid,
            userId: server.userId.toString(),
            status: server.status ?? 'ready',
        },
    })

    const submit = async (_data: any) => {
        const { status, ...data } = _data as z.infer<typeof schema>

        clearFlashes()
        try {
            await updateServer(server.uuid, {
                status: status === 'ready' ? null : (status as EloquentStatus),
                ...data,
            })

            setServer({
                ...server,
                status: status === 'ready' ? null : (status as EloquentStatus),
                ...data,
            })

            form.reset({
                name: data.name,
                hostname: data.hostname,
                vmid: data.vmid,
                userId: data.userId.toString(),
                status: status ?? 'ready',
            })
        } catch (error) {
            clearAndAddHttpError(error as any)
        }
    }

    const statusTypes = [
        { value: 'ready', label: t('server_info.statuses.ready') },
        { value: 'installing', label: t('server_info.statuses.installing') },
        {
            value: 'install_failed',
            label: t('server_info.statuses.install_failed'),
        },
        { value: 'suspended', label: t('server_info.statuses.suspended') },
        {
            value: 'restoring_backup',
            label: t('server_info.statuses.restoring_backup'),
        },
        {
            value: 'restoring_snapshot',
            label: t('server_info.statuses.restoring_snapshot'),
        },
    ]

    return (
        <FormCard className='w-full'>
            <FormProvider {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <FormCard.Body>
                        <FormCard.Title>
                            {t('server_info.title')}
                        </FormCard.Title>
                        <div className='space-y-3 mt-3'>
                            <FlashMessageRender
                                byKey={`admin.servers.${server.uuid}.settings.general.info`}
                            />
                            <TextInputForm
                                name='name'
                                label={tStrings('display_name')}
                            />
                            <TextInputForm
                                name='hostname'
                                label={tStrings('hostname')}
                            />
                            <TextInputForm name='vmid' label='VMID' />
                            <UsersSelectForm />
                            <SelectForm
                                name={'status'}
                                data={statusTypes}
                                label={tStrings('status')}
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

export default ServerInformationCard
