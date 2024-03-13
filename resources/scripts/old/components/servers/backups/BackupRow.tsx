//@ts-ignore
import Dots from '@/assets/images/icons/dots-vertical.svg'
import { ServerContext } from '@/state/server'
import { bytesToString } from '@/util/helpers'
import useFlash from '@/util/useFlash'
import useNotify from '@/util/useNotify'
import { ExclamationCircleIcon } from '@heroicons/react/20/solid'
import { Loader } from '@mantine/core'
import { formatDistanceToNow } from 'date-fns'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { KeyedMutator } from 'swr'

import deleteBackup from '@/api/server/backups/deleteBackup'
import { Backup, BackupResponse } from '@/api/server/backups/getBackups'
import restoreBackup from '@/api/server/backups/restoreBackup'

import DottedButton from '@/components/elements/DottedButton'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Menu from '@/components/elements/Menu'
import Modal from '@/components/elements/Modal'
import Display from '@/components/elements/displays/DisplayRow'


interface Props {
    backup: Backup
    swr: {
        mutate: KeyedMutator<BackupResponse>
    }
}

interface DropdownProps {
    className?: string
    backup: Backup
    swr: {
        mutate: KeyedMutator<BackupResponse>
    }
}

const Dropdown = ({ className, backup, swr: { mutate } }: DropdownProps) => {
    const { t } = useTranslation('server.backups')
    const { t: tStrings } = useTranslation('strings')
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const server = ServerContext.useStoreState(state => state.server.data!)
    const setServer = ServerContext.useStoreActions(
        actions => actions.server.setServer
    )
    const notify = useNotify()
    const [deleteModalOpen, setDeleteModalOpen] = useState(false)
    const [restoreModalOpen, setRestoreModalOpen] = useState(false)

    const handleRestore = async () => {
        clearFlashes(`servers.backups.${backup.uuid}.restore`)
        try {
            await restoreBackup(server.uuid, backup.uuid)

            setServer({
                ...server,
                status: 'restoring_backup',
            })
            notify({
                message: t('notices.backup_restored', {
                    name: backup.name,
                }),
                color: 'green',
            })
        } catch (error) {
            clearAndAddHttpError({
                key: `servers.backups.${backup.uuid}.restore`,
                error,
            })
        }
    }

    const handleDelete = async () => {
        clearFlashes(`servers.backups.${backup.uuid}.delete`)
        try {
            await deleteBackup(server.uuid, backup.uuid)

            mutate(
                data =>
                    ({
                        ...data,
                        items: data!.items.filter(b => b.uuid !== backup.uuid),
                        backupCount: data!.backupCount - 1,
                    }) as BackupResponse,
                false
            )

            notify({
                message: t('notices.backup_deleted', {
                    name: backup.name,
                }),
                color: 'green',
            })
        } catch (error) {
            clearAndAddHttpError({
                key: `servers.backups.${backup.uuid}.delete`,
                error,
            })
        }
    }

    return (
        <>
            <Modal
                open={deleteModalOpen}
                onClose={() => setDeleteModalOpen(e => !e)}
            >
                <Modal.Header>
                    <Modal.Title>
                        {t('delete_modal.title', {
                            name: backup.name,
                        })}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Modal.Description>
                        {t('delete_modal.description')}
                    </Modal.Description>

                    <FlashMessageRender
                        byKey={`servers.backups.${backup.uuid}.delete`}
                        className='py-5'
                    />
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action onClick={() => setDeleteModalOpen(e => !e)}>
                        {tStrings('cancel')}
                    </Modal.Action>
                    <Modal.Action onClick={handleDelete}>
                        {tStrings('delete')}
                    </Modal.Action>
                </Modal.Actions>
            </Modal>
            <Modal
                open={restoreModalOpen}
                onClose={() => setRestoreModalOpen(e => !e)}
            >
                <Modal.Header>
                    <Modal.Title>
                        {t('restore_modal.title', {
                            name: backup.name,
                        })}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Modal.Description>
                        {t('restore_modal.description')}
                    </Modal.Description>

                    <FlashMessageRender
                        byKey={`servers.backups.${backup.uuid}.restore`}
                        className='py-5'
                    />
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action onClick={() => setRestoreModalOpen(e => !e)}>
                        {tStrings('cancel')}
                    </Modal.Action>
                    <Modal.Action onClick={handleRestore}>
                        {tStrings('restore')}
                    </Modal.Action>
                </Modal.Actions>
            </Modal>
            <Menu width={200}>
                <Menu.Target>
                    <DottedButton className={className} />
                </Menu.Target>
                <Menu.Dropdown>
                    {backup.isSuccessful ? (
                        <Menu.Item onClick={() => setRestoreModalOpen(true)}>
                            {tStrings('restore')}
                        </Menu.Item>
                    ) : null}
                    <Menu.Item
                        color='red'
                        onClick={() => setDeleteModalOpen(true)}
                    >
                        {tStrings('delete')}
                    </Menu.Item>
                </Menu.Dropdown>
            </Menu>
        </>
    )
}

const BackupRow = ({ backup, swr: { mutate } }: Props) => {
    return (
        <Display.Row
            key={backup.uuid}
            className='grid-cols-1 md:grid-cols-8 text-sm'
        >
            <div className='flex justify-between items-center grow'>
                <div className='flex items-center space-x-3'>
                    <p className='overflow-hidden text-ellipsis font-semibold text-foreground'>
                        {backup.name}
                    </p>
                    {!backup.completedAt ? (
                        <Loader size='xs' />
                    ) : (
                        !backup.isSuccessful && (
                            <ExclamationCircleIcon className='h-5 w-5 text-error' />
                        )
                    )}
                </div>

                {backup.completedAt && (
                    <Dropdown
                        swr={{ mutate }}
                        backup={backup}
                        className='md:hidden'
                    />
                )}
            </div>
            <div>{bytesToString(backup.size)}</div>
            <div className='flex justify-between items-center md:col-span-2'>
                <p>
                    {formatDistanceToNow(backup.createdAt, {
                        includeSeconds: true,
                        addSuffix: true,
                    })}
                </p>

                {backup.completedAt && (
                    <Dropdown
                        swr={{ mutate }}
                        backup={backup}
                        className='hidden md:block'
                    />
                )}
            </div>
        </Display.Row>
    )
}

export default BackupRow