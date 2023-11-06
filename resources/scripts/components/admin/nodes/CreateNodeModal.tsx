import { useFlashKey } from '@/util/useFlash'
import { hostname } from '@/util/validation'
import { zodResolver } from '@hookform/resolvers/zod'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'

import createNode from '@/api/admin/nodes/createNode'
import useNodesSWR from '@/api/admin/nodes/useNodesSWR'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import MessageBox from '@/components/elements/MessageBox'
import Modal from '@/components/elements/Modal'
import TextInputForm from '@/components/elements/forms/TextInputForm'

import LocationsSelectForm from '@/components/admin/nodes/LocationsSelectForm'

interface Props {
    open: boolean
    onClose: () => void
}

const CreateNodeModal = ({ open, onClose }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        'admin.nodes.index.create'
    )
    const { mutate } = useNodesSWR({ page: 1, query: '' })
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.nodes.index')

    const schema = z.object({
        name: z.string().max(191).nonempty(),
        locationId: z.preprocess(Number, z.number()),
        cluster: z.string().max(191).nonempty(),
        tokenId: z.string().max(191).nonempty(),
        secret: z.string().max(191).nonempty(),
        fqdn: hostname().max(191).nonempty(),
        port: z.preprocess(Number, z.number().int().min(1).max(65535)),
        memory: z.preprocess(Number, z.number().int().min(0)),
        memoryOverallocate: z.preprocess(Number, z.number().int().min(0)),
        disk: z.preprocess(Number, z.number().int().min(0)),
        diskOverallocate: z.preprocess(Number, z.number().int().min(0)),
        vmStorage: z.string().max(191).nonempty(),
        backupStorage: z.string().max(191).nonempty(),
        isoStorage: z.string().max(191).nonempty(),
        network: z.string().max(191).nonempty(),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: '',
            locationId: '0',
            cluster: '',
            tokenId: '',
            secret: '',
            fqdn: '',
            port: '8006',
            memory: '0',
            memoryOverallocate: '0',
            disk: '0',
            diskOverallocate: '0',
            vmStorage: '',
            backupStorage: '',
            isoStorage: '',
            network: '',
        },
    })

    const handleClose = () => {
        clearFlashes()
        form.reset()
        onClose()
    }

    const submit = async (_data: any) => {
        const { memory, disk, ...data } = _data as z.infer<typeof schema>
        clearFlashes()
        try {
            const node = await createNode({
                memory: memory * 1048576,
                disk: disk * 1048576,
                ...data,
            })

            mutate(data => {
                if (!data) return data

                return {
                    ...data,
                    items: [node, ...data.items],
                }
            }, false)

            handleClose()
        } catch (error) {
            clearAndAddHttpError(error as Error)
        }
    }

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>{t('create_modal.title')}</Modal.Title>
            </Modal.Header>

            <FormProvider {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Modal.Body>
                        <FlashMessageRender
                            className='mb-5'
                            byKey={'admin.nodes.index.create'}
                        />
                        <TextInputForm
                            name='name'
                            label={tStrings('display_name')}
                        />
                        <LocationsSelectForm />
                        <TextInputForm name='cluster' label={t('pve_name')} />
                        <MessageBox
                            className='mt-3'
                            title='Warning'
                            type='warning'
                        >
                            {t('creds_warning')}
                        </MessageBox>
                        <div className='grid gap-3 grid-cols-2'>
                            <TextInputForm
                                name='tokenId'
                                label={t('token_id')}
                            />
                            <TextInputForm name='secret' label={t('secret')} />
                        </div>
                        <TextInputForm name='fqdn' label={tStrings('fqdn')} />
                        <TextInputForm name='port' label={tStrings('port')} />
                        <div className='grid gap-3 grid-cols-2'>
                            <TextInputForm
                                name='memory'
                                label={`${t('memory_allocation')} (MiB)`}
                            />
                            <TextInputForm
                                name='memoryOverallocate'
                                label={`${t('memory_overallocation')} (%)`}
                            />
                        </div>
                        <div className='grid gap-3 grid-cols-2'>
                            <TextInputForm
                                name='disk'
                                label={`${t('disk_allocation')} (MiB)`}
                            />
                            <TextInputForm
                                name='diskOverallocate'
                                label={`${t('disk_allocation')} (MiB)`}
                            />
                        </div>
                        <div className='grid gap-3 grid-cols-3'>
                            <TextInputForm
                                name='vmStorage'
                                label={t('vm_storage')}
                                placeholder='local'
                            />
                            <TextInputForm
                                name='backupStorage'
                                label={t('backup_storage')}
                                placeholder='local'
                            />
                            <TextInputForm
                                name='isoStorage'
                                label={t('iso_storage')}
                                placeholder='local'
                            />
                        </div>
                        <TextInputForm
                            name='network'
                            label={tStrings('network')}
                            placeholder='vmbr0'
                        />
                    </Modal.Body>
                    <Modal.Actions>
                        <Modal.Action type='button' onClick={handleClose}>
                            {tStrings('cancel')}
                        </Modal.Action>
                        <Modal.Action
                            type='submit'
                            loading={form.formState.isSubmitting}
                        >
                            {tStrings('create')}
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormProvider>
        </Modal>
    )
}

export default CreateNodeModal
