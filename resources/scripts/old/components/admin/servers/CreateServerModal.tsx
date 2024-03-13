import { useFlashKey } from '@/util/useFlash'
import usePagination from '@/util/usePagination'
import { hostname, password, usKeyboardCharacters } from '@/util/validation'
import { zodResolver } from '@hookform/resolvers/zod'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'

import createServer from '@/api/admin/servers/createServer'
import { ServerResponse } from '@/api/admin/servers/getServers'
import useServersSWR from '@/api/admin/servers/useServersSWR'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import CheckboxForm from '@/components/elements/forms/CheckboxForm'
import TextInputForm from '@/components/elements/forms/TextInputForm'

import AddressesMultiSelectForm from '@/components/admin/servers/AddressesMultiSelectForm'
import NodesSelectForm from '@/components/admin/servers/NodesSelectForm'
import TemplatesSelectForm from '@/components/admin/servers/TemplatesSelectForm'
import UsersSelectForm from '@/components/admin/servers/UsersSelectForm'


interface Props {
    nodeId?: number
    userId?: number
    open: boolean
    onClose: () => void
}

const CreateServerModal = ({ nodeId, userId, open, onClose }: Props) => {
    const [page] = usePagination()
    const { mutate } = useServersSWR({
        nodeId,
        userId,
        page,
        query: '',
        include: ['node', 'user'],
    })
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        'admin.servers.create'
    )
    const { t } = useTranslation('admin.servers.index')
    const { t: tStrings } = useTranslation('strings')

    const schemaWithCreateVm = z.object({
        name: z.string().max(40).nonempty(),
        nodeId: z.preprocess(Number, z.number()),
        userId: z.preprocess(Number, z.number()),
        vmid: z.union([
            z.preprocess(Number, z.number().int().min(100).max(999999999)),
            z.literal(''),
        ]),
        hostname: hostname().max(191).nonempty(),
        addressIds: z.array(z.preprocess(Number, z.number())),
        cpu: z.preprocess(Number, z.number().min(1)),
        memory: z.preprocess(Number, z.number().min(16)),
        disk: z.preprocess(Number, z.number().min(1)),
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
        accountPassword: password(usKeyboardCharacters()).nonempty(),
        shouldCreateServer: z.literal(true),
        startOnCompletion: z.boolean(),
        templateUuid: z.string().nonempty(),
    })

    const schemaWithoutCreatingVm = z.object({
        name: z.string().max(40).nonempty(),
        nodeId: z.preprocess(Number, z.number()),
        userId: z.preprocess(Number, z.number()),
        vmid: z.union([
            z.preprocess(Number, z.number().min(100).max(999999999)),
            z.literal(''),
        ]),
        hostname: hostname().max(191).nonempty(),
        addressIds: z.array(z.preprocess(Number, z.number())),
        cpu: z.preprocess(Number, z.number().min(1)),
        memory: z.preprocess(Number, z.number().min(16)),
        disk: z.preprocess(Number, z.number().min(1)),
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
        accountPassword: password(usKeyboardCharacters()).optional(),
        shouldCreateServer: z.literal(false),
        startOnCompletion: z.boolean(),
        templateUuid: z.string(),
    })

    const schema = z.discriminatedUnion('shouldCreateServer', [
        schemaWithCreateVm,
        schemaWithoutCreatingVm,
    ])

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: '',
            nodeId: nodeId?.toString() ?? '',
            userId: userId?.toString() ?? '',
            vmid: '',
            hostname: '',
            addressIds: [],
            cpu: '',
            memory: '',
            disk: '',
            snapshotLimit: '0',
            backupLimit: '',
            bandwidthLimit: '',
            accountPassword: '',
            shouldCreateServer: true,
            startOnCompletion: false,
            templateUuid: '',
        },
    })

    const watchShouldCreateServer = form.watch('shouldCreateServer')
    const watchNodeId = form.watch('nodeId')

    const submit = async (_data: any) => {
        const {
            vmid,
            cpu,
            memory,
            disk,
            snapshotLimit,
            backupLimit,
            bandwidthLimit,
            addressIds,
            accountPassword,
            ...data
        } = _data as z.infer<typeof schema>
        clearFlashes()
        try {
            const server = await createServer({
                ...data,
                vmid: vmid !== '' ? vmid : null,
                limits: {
                    cpu,
                    memory: memory * 1048576,
                    disk: disk * 1048576,
                    snapshots: snapshotLimit !== '' ? snapshotLimit : null,
                    backups: backupLimit !== '' ? backupLimit : null,
                    bandwidth:
                        bandwidthLimit !== '' ? bandwidthLimit * 1048576 : null,
                    addressIds,
                },
                accountPassword: accountPassword ? accountPassword : null,
            })

            mutate(data => {
                if (!data) return data

                return {
                    ...data,
                    items: [server, ...data.items],
                } as ServerResponse
            }, false)

            handleClose()
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    const handleClose = () => {
        clearFlashes()
        form.reset()
        onClose()
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
                            byKey={'admin.servers.create'}
                        />
                        <TextInputForm
                            name={'name'}
                            label={tStrings('display_name')}
                        />
                        {nodeId ? null : <NodesSelectForm />}
                        {userId ? null : <UsersSelectForm />}
                        <TextInputForm
                            name={'vmid'}
                            label={'VMID'}
                            placeholder={
                                t('vmid_placeholder') ??
                                'Leave blank for random VMID'
                            }
                        />
                        <TextInputForm
                            name={'hostname'}
                            label={tStrings('hostname')}
                        />
                        <AddressesMultiSelectForm
                            disabled={watchNodeId === ''}
                        />
                        <div className={'grid grid-cols-2 gap-3'}>
                            <TextInputForm
                                name={'cpu'}
                                label={tStrings('cpu')}
                            />
                            <TextInputForm
                                name={'memory'}
                                label={`${tStrings('memory')} (MiB)`}
                            />
                        </div>
                        <TextInputForm
                            name={'disk'}
                            label={`${tStrings('disk')} (MiB)`}
                        />
                        <div className={'grid grid-cols-2 gap-3'}>
                            <TextInputForm
                                name={'backupLimit'}
                                label={t('backup_limit')}
                                placeholder={
                                    t('limit_placeholder') ??
                                    'Leave blank for no limit'
                                }
                            />
                            <TextInputForm
                                name={'bandwidthLimit'}
                                label={`${t('bandwidth_limit')} (MiB)`}
                                placeholder={
                                    t('limit_placeholder') ??
                                    'Leave blank for no limit'
                                }
                            />
                        </div>
                        <TextInputForm
                            name={'accountPassword'}
                            label={tStrings('system_os_password')}
                            type={'password'}
                        />
                        <CheckboxForm
                            name={'shouldCreateServer'}
                            label={t('should_create_vm')}
                            className={'mt-3 relative'}
                        />
                        <TemplatesSelectForm
                            disabled={
                                !watchShouldCreateServer || watchNodeId === ''
                            }
                        />
                        <CheckboxForm
                            name={'startOnCompletion'}
                            label={t('start_server_after_installing')}
                            className={'mt-3 relative'}
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

export default CreateServerModal
