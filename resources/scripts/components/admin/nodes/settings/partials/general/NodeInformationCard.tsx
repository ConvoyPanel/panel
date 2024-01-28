import { useFlashKey } from '@/util/useFlash'
import { hostname } from '@/util/validation'
import { zodResolver } from '@hookform/resolvers/zod'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'

import updateNode from '@/api/admin/nodes/updateNode'
import useNodeSWR from '@/api/admin/nodes/useNodeSWR'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import CheckboxForm from '@/components/elements/forms/CheckboxForm'
import TextInputForm from '@/components/elements/forms/TextInputForm'

import LocationsSelectForm from '@/components/admin/nodes/LocationsSelectForm'


const NodeInformationCard = () => {
    const { data: node, mutate } = useNodeSWR()
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.nodes.${node.id}.settings.general.info`
    )
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.nodes.settings')
    const { t: tIndex } = useTranslation('admin.nodes.index')

    const schema = z.object({
        name: z.string().min(1).max(191),
        locationId: z.preprocess(Number, z.number()),
        cluster: z.string().min(1).max(191),
        verifyTls: z.boolean(),
        tokenId: z.string().max(191),
        secret: z.string().max(191),
        fqdn: hostname().min(1).max(191),
        port: z.preprocess(Number, z.number().int().min(1).max(65535)),
        memory: z.preprocess(Number, z.number().int().min(0)),
        memoryOverallocate: z.preprocess(Number, z.number().int().min(0)),
        disk: z.preprocess(Number, z.number().int().min(0)),
        diskOverallocate: z.preprocess(Number, z.number().int().min(0)),
        vmStorage: z.string().min(1).max(191),
        backupStorage: z.string().min(1).max(191),
        isoStorage: z.string().min(1).max(191),
        network: z.string().min(1).max(191),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: node.name,
            locationId: node.locationId.toString(),
            cluster: node.cluster,
            verifyTls: node.verifyTls,
            tokenId: '',
            secret: '',
            fqdn: node.fqdn,
            port: node.port,
            memory: node.memory / 1048576,
            memoryOverallocate: node.memoryOverallocate,
            disk: node.disk / 1048576,
            diskOverallocate: node.diskOverallocate,
            vmStorage: node.vmStorage,
            backupStorage: node.backupStorage,
            isoStorage: node.isoStorage,
            network: node.network,
        },
    })

    const submit = async (_data: any) => {
        const { memory, disk, ...data } = _data as z.infer<typeof schema>
        clearFlashes()
        try {
            const updatedNode = await updateNode(node.id, {
                ...data,
                memory: memory * 1048576,
                disk: disk * 1048576,
            })

            mutate(() => updatedNode, false)

            form.reset({
                name: data.name,
                locationId: data.locationId.toString(),
                cluster: data.cluster,
                verifyTls: data.verifyTls,
                tokenId: '',
                secret: '',
                fqdn: data.fqdn,
                port: data.port,
                memory,
                memoryOverallocate: data.memoryOverallocate,
                disk,
                diskOverallocate: data.diskOverallocate,
                vmStorage: data.vmStorage,
                backupStorage: data.backupStorage,
                isoStorage: data.isoStorage,
                network: data.network,
            })
        } catch (error) {
            clearAndAddHttpError(error as Error)
        }
    }

    return (
        <FormCard className='w-full'>
            <FormProvider {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <FormCard.Body>
                        <FormCard.Title>{t('node_info.title')}</FormCard.Title>
                        <div className='space-y-3 mt-3'>
                            <FlashMessageRender
                                byKey={`admin.nodes.${node.id}.settings.general.info`}
                            />
                            <TextInputForm
                                name='name'
                                label={tStrings('display_name')}
                            />
                            <LocationsSelectForm />
                            <TextInputForm
                                name='cluster'
                                label={tIndex('pve_name')}
                            />
                            <TextInputForm
                                name='fqdn'
                                label={tStrings('fqdn')}
                            />
                            <CheckboxForm
                                name={'verifyTls'}
                                label='Verify TLS Certificate'
                                className={'relative'}
                            />
                            <TextInputForm
                                name='port'
                                label={tStrings('port')}
                            />
                            <div className='grid gap-3 grid-cols-2'>
                                <TextInputForm
                                    name='tokenId'
                                    label={tIndex('token_id')}
                                    placeholder={
                                        tIndex('override_creds') ??
                                        'Override credentials'
                                    }
                                />
                                <TextInputForm
                                    name='secret'
                                    label={tIndex('secret')}
                                    placeholder={
                                        tIndex('override_creds') ??
                                        'Override credentials'
                                    }
                                />
                            </div>
                            <div className='grid gap-3 grid-cols-2'>
                                <TextInputForm
                                    name='memory'
                                    label={`${tStrings('memory')} (MiB)`}
                                />
                                <TextInputForm
                                    name='memoryOverallocate'
                                    label={`${tIndex(
                                        'memory_overallocation'
                                    )} (%)`}
                                />
                            </div>
                            <div className='grid gap-3 grid-cols-2'>
                                <TextInputForm
                                    name='disk'
                                    label={`${tStrings('disk')} (MiB)`}
                                />
                                <TextInputForm
                                    name='diskOverallocate'
                                    label={`${tIndex(
                                        'disk_overallocation'
                                    )} (%)`}
                                />
                            </div>
                            <div className='grid gap-3 grid-cols-3'>
                                <TextInputForm
                                    name='vmStorage'
                                    label={tIndex('vm_storage')}
                                    placeholder='local'
                                />
                                <TextInputForm
                                    name='backupStorage'
                                    label={tIndex('backup_storage')}
                                    placeholder='local'
                                />
                                <TextInputForm
                                    name='isoStorage'
                                    label={tIndex('iso_storage')}
                                    placeholder='local'
                                />
                            </div>
                            <TextInputForm
                                name='network'
                                label={tStrings('network')}
                                placeholder='vmbr0'
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

export default NodeInformationCard
