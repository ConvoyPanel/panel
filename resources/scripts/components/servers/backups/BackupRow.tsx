import { Backup, BackupResponse } from '@/api/server/backups/getBackups'
import Display from '@/components/elements/displays/DisplayRow'
import { bytesToString } from '@/util/helpers'
import { Loader } from '@mantine/core'
import { formatDistanceToNow } from 'date-fns'
//@ts-ignore
import Dots from '@/assets/images/icons/dots-vertical.svg'
import { ComponentProps, forwardRef, useState } from 'react'
import Menu from '@/components/elements/Menu'
import { ExclamationCircleIcon } from '@heroicons/react/20/solid'
import useFlash from '@/util/useFlash'
import restoreBackup from '@/api/server/backups/restoreBackup'
import { ServerContext } from '@/state/server'
import useNotify from '@/util/useNotify'
import deleteBackup from '@/api/server/backups/deleteBackup'
import { KeyedMutator } from 'swr'
import Modal from '@/components/elements/Modal'

interface Props {
    backup: Backup
    swr: {
        mutate: KeyedMutator<BackupResponse>
    }
}

export const DottedButton = forwardRef<HTMLButtonElement, Omit<ComponentProps<'button'>, 'children'>>(
    ({ className, ...props }, ref) => {
        return (
            <button ref={ref} className={`px-2 bg-transparent ${className}`} {...props}>
                <img src={Dots} className='w-4 h-4 min-w-[1rem] dark:invert' alt='3 vertical dots meant for activating a menu' />
            </button>
        )
    }
)

interface DropdownProps {
    className?: string
    backup: Backup
    swr: {
        mutate: KeyedMutator<BackupResponse>
    }
}

const Dropdown = ({ className, backup, swr: { mutate } }: DropdownProps) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const server = ServerContext.useStoreState(state => state.server.data!)
    const setServer = ServerContext.useStoreActions(actions => actions.server.setServer)
    const notify = useNotify()
    const [deleteModalOpen, setDeleteModalOpen] = useState(false)
    const [restoreModalOpen, setRestoreModalOpen] = useState(false)

    const handleRestore = async () => {
        setRestoreModalOpen(false)
        clearFlashes('backups')
        try {
            await restoreBackup(server.uuid, backup.uuid)
        } catch (error) {
            console.error(error)
            clearAndAddHttpError({ key: 'backups', error })
            notify({
                title: 'Failed to Restore',
                message: `Failed to Restore ${backup.name}`,
                color: 'red',
            })
            return
        }

        setServer({
          ...server,
          status: 'restoring_backup'
        })
        notify({
            title: 'Restored Backup',
            message: `Restored From ${backup.name}`,
            color: 'green',
        })
    }

    const handleDelete = async () => {
        setDeleteModalOpen(false)
        clearFlashes('backups')
        try {
            await deleteBackup(server.uuid, backup.uuid)
        } catch (error) {
            console.error(error)
            clearAndAddHttpError({ key: 'backups', error })
            notify({
                title: 'Failed to Delete Backup',
                message: `Failed to Delete ${backup.name}`,
                color: 'red',
            })
            return
        }

        mutate(
            data =>
                ({
                    ...data,
                    items: data!.items.filter(b => b.uuid !== backup.uuid),
                    backupCount: data!.backupCount - 1,
                } as BackupResponse),
            false
        )

        notify({
            title: 'Deleted Backup',
            message: `Deleted ${backup.name}`,
            color: 'green',
        })
    }

    return (
        <>
            <Modal open={deleteModalOpen} onClose={() => setDeleteModalOpen(e => !e)}>
                <Modal.Header>
                    <Modal.Title>Delete {backup.name}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Modal.Description>
                        Are you sure you want to delete this backup? This action is irreversible.
                    </Modal.Description>
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action onClick={() => setDeleteModalOpen(e => !e)}>Cancel</Modal.Action>
                    <Modal.Action onClick={handleDelete}>Delete</Modal.Action>
                </Modal.Actions>
            </Modal>
            <Modal open={restoreModalOpen} onClose={() => setRestoreModalOpen(e => !e)}>
                <Modal.Header>
                    <Modal.Title>Restore From {backup.name}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Modal.Description>
                        Are you sure you want to restore from this backup? This action is irreversible.
                    </Modal.Description>
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action onClick={() => setRestoreModalOpen(e => !e)}>Cancel</Modal.Action>
                    <Modal.Action onClick={handleRestore}>Restore</Modal.Action>
                </Modal.Actions>
            </Modal>
            <Menu>
                <Menu.Button>
                    <DottedButton className={className} />
                </Menu.Button>
                <Menu.Items>
                    {backup.isSuccessful ? (
                        <Menu.Item onClick={() => setRestoreModalOpen(true)}>Restore</Menu.Item>
                    ) : null}
                    <Menu.Item color='danger' onClick={() => setDeleteModalOpen(true)}>
                        Delete
                    </Menu.Item>
                </Menu.Items>
            </Menu>
        </>
    )
}

const BackupRow = ({ backup, swr: { mutate } }: Props) => {
    return (
        <Display.Row key={backup.uuid} className='grid-cols-1 md:grid-cols-8 text-sm'>
            <div className='flex justify-between items-center md:col-span-5'>
                <div className='flex items-center space-x-3'>
                    <p className='overflow-hidden text-ellipsis font-semibold text-foreground'>{backup.name}</p>
                    {!backup.completedAt ? (
                        <Loader size='xs' />
                    ) : (
                        !backup.isSuccessful && <ExclamationCircleIcon className='h-5 w-5 text-error' />
                    )}
                </div>

                {backup.completedAt && <Dropdown swr={{ mutate }} backup={backup} className='md:hidden' />}
            </div>
            <div>{bytesToString(backup.size)}</div>
            <div className='flex justify-between items-center md:col-span-2'>
                <p>
                    {formatDistanceToNow(backup.createdAt, {
                        includeSeconds: true,
                        addSuffix: true,
                    })}
                </p>

                {backup.completedAt && <Dropdown swr={{ mutate }} backup={backup} className='hidden md:block' />}
            </div>
        </Display.Row>
    )
}

export default BackupRow
