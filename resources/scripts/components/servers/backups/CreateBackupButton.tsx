import { useStoreState } from '@/state'
import { ServerContext } from '@/state/server'
import { useFlashKey } from '@/util/useFlash'
import { zodResolver } from '@hookform/resolvers/zod'
import { Badge, Tooltip } from '@mantine/core'
import { useState } from 'react'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { KeyedMutator } from 'swr'
import { z } from 'zod'

import createBackup from '@/api/server/backups/createBackup'
import { BackupResponse } from '@/api/server/backups/getBackups'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import SelectForm from '@/components/elements/forms/SelectForm'
import TextInputForm from '@/components/elements/forms/TextInputForm'


interface Props {
    swr: {
        mutate: KeyedMutator<BackupResponse>
    }
    backupCount?: number
}

const CreateBackupButton = ({ swr: { mutate }, backupCount }: Props) => {
    const { t } = useTranslation('server.backups')
    const { t: tStrings } = useTranslation('strings')
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const [open, setOpen] = useState(false)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `servers.${uuid}.backups.create`
    )
    const backupLimit = ServerContext.useStoreState(
        state => state.server.data!.limits.backups
    )
    const theme = useStoreState(state => state.settings.data?.theme)

    const schema = z.object({
        name: z.string().nonempty().max(40),
        compressionType: z.enum(['none', 'lzo', 'gzip', 'zstd']),
        mode: z.enum(['snapshot', 'suspend', 'kill']),
    })

    const methods = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: '',
            compressionType: 'zstd' as z.infer<
                typeof schema
            >['compressionType'],
            mode: 'snapshot' as z.infer<typeof schema>['mode'],
        },
    })

    const submit = async (data: z.infer<typeof schema>) => {
        clearFlashes()

        try {
            const backup = await createBackup(uuid, {
                ...data,
                locked: false,
            })

            mutate(
                data =>
                    ({
                        ...data,
                        items: [backup].concat(data!.items),
                        backupCount: data!.backupCount + 1,
                    }) as BackupResponse,
                false
            )

            handleClose()
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    const handleClose = () => {
        clearFlashes()
        methods.reset()
        setOpen(false)
    }

    const compressionTypes = [
        { label: tStrings('none'), value: 'none' },
        { label: 'LZO', value: 'lzo' },
        { label: 'GZip', value: 'gzip' },
        { label: 'ZSTD', value: 'zstd' },
    ]

    const modes = [
        { label: t('create_modal.modes.snapshot'), value: 'snapshot' },
        { label: t('create_modal.modes.suspend'), value: 'suspend' },
        { label: t('create_modal.modes.kill'), value: 'kill' },
    ]

    return (
        <>
            <Modal open={open} onClose={handleClose}>
                <Modal.Header>
                    <Modal.Title>{t('create_modal.title')}</Modal.Title>
                </Modal.Header>
                <FormProvider {...methods}>
                    <form onSubmit={methods.handleSubmit(submit)}>
                        <Modal.Body>
                            <Modal.Description bottomMargin>
                                {t('create_modal.description')}
                            </Modal.Description>

                            <FlashMessageRender
                                byKey={`servers.${uuid}.backups.create`}
                                className='mb-5'
                            />

                            <TextInputForm
                                name='name'
                                placeholder='Name'
                                label={tStrings('name')}
                            />

                            <div className='grid sm:grid-cols-2 mt-3 gap-3'>
                                <SelectForm
                                    label={t('create_modal.compression_type')}
                                    name='compressionType'
                                    data={compressionTypes}
                                />
                                <SelectForm
                                    label={t('create_modal.mode')}
                                    name='mode'
                                    data={modes}
                                />
                            </div>
                        </Modal.Body>
                        <Modal.Actions>
                            <Modal.Action type='button' onClick={handleClose}>
                                {tStrings('cancel')}
                            </Modal.Action>
                            <Modal.Action
                                type='submit'
                                loading={methods.formState.isSubmitting}
                            >
                                {tStrings('create')}
                            </Modal.Action>
                        </Modal.Actions>
                    </form>
                </FormProvider>
            </Modal>
            <div className='flex justify-end items-center space-x-3 mb-3'>
                <Tooltip
                    label={t('counter_tooltip', {
                        count: backupCount,
                        max: backupLimit ?? tStrings('unlimited').toLowerCase(),
                    })}
                    position='bottom'
                    withArrow
                >
                    <Badge
                        color={theme === 'dark' ? 'gray' : 'dark'}
                        variant='outline'
                    >
                        {backupCount ?? 0}/
                        {backupLimit ?? tStrings('unlimited')}
                    </Badge>
                </Tooltip>
                <Button
                    disabled={
                        backupCount !== undefined
                            ? backupLimit !== null
                                ? backupCount >= backupLimit
                                : false
                            : true
                    }
                    onClick={() => setOpen(true)}
                    variant='filled'
                >
                    {t('create_backup')}
                </Button>
            </div>
        </>
    )
}

export default CreateBackupButton