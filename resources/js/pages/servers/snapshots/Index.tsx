import { Snapshot } from '@/api/server/snapshots/types'
import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerNav from '@/components/servers/ServerNav'
import {
  CheckIcon,
  PlayIcon,
  PlusIcon,
  TrashIcon,
} from '@heroicons/react/solid'
import { Head, useForm } from '@inertiajs/inertia-react'
import { Button, Modal, Paper, Table, TextInput, Tooltip } from '@mantine/core'
import { useMemo, useState } from 'react'
import RoundedButton from '@/components/RoundedButton'
import { Inertia } from '@inertiajs/inertia'
import dateTimeCalculator from '@/util/dateTimeCalculator'
import { ArchiveIcon, DuplicateIcon } from '@heroicons/react/outline'
import EmptyState from '@/components/EmptyState'
import DeleteButton from '@/components/elements/tables/DeleteButton'

interface SnapshotProps {
  server: Server
  snapshot: Snapshot
  currentSnapshot?: Snapshot
}

const SnapshotRow = ({ server, snapshot, currentSnapshot }: SnapshotProps) => {
  const [showDeleteModal, setShowDeleteModal] = useState(false)
  const [showRollbackModal, setShowRollbackModal] = useState(false)
  const { delete: deleteSnapshot, processing: processingDelete } = useForm({})
  const { post: rollbackSnapshot, processing: processingRollback } = useForm({})

  const handleDelete = async () => {
    deleteSnapshot(
      route('servers.show.snapshots', {
        name: snapshot.name,
        server: server.uuidShort,
      }),
      {
        onSuccess: () => setShowDeleteModal(false),
      }
    )
  }

  const handleRollback = async () => {
    rollbackSnapshot(
      route('servers.show.snapshots.rollback', { server: server.uuidShort }),
      {
        onSuccess: () => setShowRollbackModal(false),
      }
    )
  }

  if (snapshot.snaptime === undefined && snapshot.running !== undefined) {
    return <></> // remove "current" from the list
  }

  const { hours, minutes, month, day, year } = dateTimeCalculator(
    snapshot?.snaptime || 0
  )

  const minutesString = minutes < 10 ? `0${minutes}` : minutes

  return (
    <>
      <Modal
        opened={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        title={`Delete ${snapshot.name}?`}
        centered
      >
        <p className='p-desc'>
          Are you sure you want to delete this snapshot? This action is
          permanent and IRREVERSIBLE.
        </p>

        <Button
          loading={processingDelete}
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
        title={`Rollback ${snapshot.name}?`}
        centered
      >
        <p className='p-desc'>
          Are you sure you want to rollback to this snapshot? You may lose your
          work if you're working on your server right now.
          <br />
          <br />
          <span className='font-bold'>
            Rollbacks don't always process through. If this snapshot isn't
            marked active after a moment, check your server's logs on this
            panel. It most likely didn't process because the snapshot you're
            rollbacking isn't the most recent one.
          </span>
        </p>

        <Button
          loading={processingRollback}
          color='primary'
          className='mt-3'
          fullWidth
          onClick={() => handleRollback()}
        >
          I understand the consequences.
        </Button>
      </Modal>
      <tr key={snapshot.digest}>
        <td>{snapshot.name}</td>
        <td>{snapshot.description}</td>
        <td>
          {snapshot.name === currentSnapshot?.parent && (
            <CheckIcon className='text-green-600 w-[18px] h-[18px]' />
          )}
        </td>
        <td>{`${hours}:${minutesString} ${month} ${day}, ${year}`}</td>
        <td>
          <RoundedButton onClick={() => setShowRollbackModal(true)}>
            <PlayIcon className='text-gray-600 hover:text-blue-600 w-[18px] h-[18px]' />
          </RoundedButton>
          <DeleteButton onClick={() => setShowDeleteModal(true)} />
        </td>
      </tr>
    </>
  )
}

interface Props extends DefaultProps {
  server: Server
  snapshots: Snapshot[]
  can_create: boolean
}

const Index = ({ auth, server, snapshots, can_create }: Props) => {
  const [showCreateModal, setShowCreateModal] = useState(false)
  const { post, errors, processing, data, setData } = useForm({
    name: '',
  })

  const handleCreate = async () => {
    await post(route('servers.show.snapshots', { server: server.uuidShort }), {
      onSuccess: () => setShowCreateModal(false),
    })
  }

  const currentSnapshot = useMemo(() => {
    return snapshots.find(
      (snapshot) => snapshot.name === 'current' && snapshot.running
    )
  }, [snapshots])

  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.uuidShort} />}
    >
      <Head title={`${server.name} - Snapshots`} />

      <Main>
        <Modal
          opened={showCreateModal}
          onClose={() => setShowCreateModal(false)}
          title={`Take a new snapshot`}
          centered
        >
          <form
            onSubmit={(e) => {
              e.preventDefault()
              handleCreate()
            }}
          >
            <TextInput
              label='Name'
              name='name'
              value={data.name}
              styles={{
                required: { display: 'none' },
              }}
              className='block w-full'
              autoFocus
              onChange={(e) =>
                setData('name', e.target.value.replace(/\s+/g, '-'))
              }
              error={errors.name}
              required
            />
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
          <h3 className='h3-deemphasized'>Snapshots</h3>
          <Paper shadow='xs' className='p-card w-full mt-3'>
            <div className='flex justify-end'>
              <Button
                disabled={!can_create}
                onClick={() => setShowCreateModal(true)}
              >
                New Snapshot
              </Button>
            </div>

            <div className='overflow-auto'>
              <Table className='mt-3' striped highlightOnHover>
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Active</th>
                    <th>Date</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  {snapshots.map((snapshot) => (
                    <SnapshotRow
                      server={server}
                      currentSnapshot={currentSnapshot}
                      snapshot={snapshot}
                      key={snapshot.digest}
                    />
                  ))}
                </tbody>
              </Table>
            </div>

            {snapshots.length <= 1 && (
              <EmptyState
                icon={DuplicateIcon}
                title='No Snapshots'
                description='Get started by creating a new snapshot.'
                action='New Snapshot'
                onClick={() => setShowCreateModal(true)}
                disabled={!can_create}
              />
            )}
          </Paper>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Index
