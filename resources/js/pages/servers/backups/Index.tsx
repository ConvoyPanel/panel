import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerNav from '@/components/servers/ServerNav'
import { Head, useForm } from '@inertiajs/inertia-react'
import { Button, Modal, Paper, Select, Table, TextInput } from '@mantine/core'
import { PlayIcon, PlusIcon, TrashIcon } from '@heroicons/react/solid'
import { Backup } from '@/api/server/backups/types'
import { DuplicateIcon } from '@heroicons/react/outline'
import EmptyState from '@/components/EmptyState'
import dateTimeCalculator from '@/util/dateTimeCalculator'
import { formatBytes } from '@/api/server/getStatus'
import { ChangeEvent, useState } from 'react'
import { formDataHandler } from '@/util/helpers'
import RoundedButton from '@/components/RoundedButton'

interface BackupRowProps {
  backup: Backup
}

const BackupRow = ({ backup }: BackupRowProps) => {
  const [showDeleteModal, setShowDeleteModal] = useState(false)
  const [showRollbackModal, setShowRollbackModal] = useState(false)
  const [processing, setProcessing] = useState(false)

  const calculateTime = (time: number) => {
    const { hours, minutes, month, day, year } = dateTimeCalculator(time)
    // add missing zero in minutes
    const minutesString = minutes < 10 ? `0${minutes}` : minutes
    return `${hours}:${minutesString} ${month} ${day}, ${year}`
  }

  const size = formatBytes(backup.size)

  const handleDelete = async () => {}

  const handleRollback = async () => {}

  return (
    <>
      <Modal
        opened={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        title={`Delete ${backup.volid.split('/')[1]}?`}
        centered
      >
        <p className='p-desc'>
          Are you sure you want to delete this backup? This action is permanent
          and IRREVERSIBLE.
        </p>

        <Button
          loading={processing}
          color='red'
          className='mt-3'
          fullWidth
          onClick={() => handleDelete()}
        >
          I understand the consequences.
        </Button>
      </Modal>
      <Modal
        opened={showRollbackModal}
        onClose={() => setShowRollbackModal(false)}
        title={`Rollback ${backup.volid.split('/')[1]}?`}
        centered
      >
        <p className='p-desc'>
          Are you sure you want to rollback your virtual machine to this backup? You will LOSE ALL UNSAVED DATA that are not in this backup (e.g. files in your current VM state).
        </p>

        <Button
          loading={processing}
          color='primary'
          className='mt-3'
          fullWidth
          onClick={() => handleRollback()}
        >
          I understand the consequences.
        </Button>
      </Modal>

      <tr key={backup.volid}>
        <td>{backup.volid.split('/')[1]}</td>
        <td>{calculateTime(backup.ctime)}</td>
        <td>{backup.format}</td>
        <td>
          {size.size} {size.unit}
        </td>
        <td>
          <RoundedButton onClick={() => setShowRollbackModal(true)}>
            <PlayIcon className='text-gray-600 hover:text-blue-600 w-[18px] h-[18px]' />
          </RoundedButton>
          <RoundedButton onClick={() => setShowDeleteModal(true)}>
            <TrashIcon className='text-gray-600 hover:text-red-600 w-[18px] h-[18px]' />
          </RoundedButton>
        </td>
      </tr>
    </>
  )
}

interface Props extends DefaultProps {
  server: Server
  backups: Backup[]
}

const Index = ({ auth, server, backups }: Props) => {
  const [showCreateModal, setShowCreateModal] = useState(false)

  const { data, setData, post, processing, errors, reset } = useForm({
    mode: 'snapshot',
    compression: 'zstd',
  })

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

  const handleCreate = () => {
    post(route('servers.show.backups.store', server.id))

    setShowCreateModal(false)
  }

  const modes = [
    { value: 'snapshot', label: 'Snapshot' },
    { value: 'suspend', label: 'Suspend' },
    { value: 'stop', label: 'Stop' },
  ]

  const compressionTypes = [
    { value: 'none', label: 'None' },
    { value: 'lzo', label: 'LZO (fast)' },
    { value: 'gzip', label: 'GZIP (awesome)' },
    { value: 'zstd', label: 'ZSTD (fast and awesome)' },
  ]

  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - Backups`} />

      <Main>
        <Modal
          opened={showCreateModal}
          onClose={() => setShowCreateModal(false)}
          title={`Take a new backup`}
          centered
        >
          <form
            onSubmit={(e) => {
              e.preventDefault()
              handleCreate()
            }}
          >
            <div className='flex flex-col space-y-1'>
              <Select
                label='Mode'
                value={data.mode}
                onChange={(e) => setData('mode', e!)}
                data={modes}
                error={errors.mode}
              />
              <Select
                label='Compression'
                value={data.compression}
                onChange={(e) => setData('compression', e!)}
                data={compressionTypes}
                error={errors.compression}
              />
            </div>
            <Button
              type='submit'
              loading={processing}
              className='mt-3'
              fullWidth
              onClick={() => handleCreate()}
            >
              Create
            </Button>
          </form>
        </Modal>
        <div>
          <h3 className='h3-deemphasized'>Backups</h3>
          <Paper shadow='xs' className='p-card w-full mt-3'>
            <div className='flex justify-end'>
              <Button onClick={() => setShowCreateModal(true)}>
                New Backup
              </Button>
            </div>
            <Table striped highlightOnHover>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Date</th>
                  <th>Format</th>
                  <th>Size</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {backups.map((backup) => (
                  <BackupRow backup={backup} />
                ))}
              </tbody>
            </Table>

            {backups.length === 0 && (
              <EmptyState
                icon={DuplicateIcon}
                title='No Backups'
                description='You have no backups yet.'
                action='New Backup'
                onClick={() => setShowCreateModal(true)}
              />
            )}
          </Paper>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Index
