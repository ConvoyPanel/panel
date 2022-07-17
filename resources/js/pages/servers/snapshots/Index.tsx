import { Snapshot } from '@/api/server/snapshots/types'
import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/types/server'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerNav from '@/components/servers/ServerNav'
import { PlayIcon, TrashIcon } from '@heroicons/react/solid'
import { Head } from '@inertiajs/inertia-react'
import { Button, Modal, Paper, Table } from '@mantine/core'
import { ComponentProps, useState } from 'react'
import RoundedButton from '@/components/RoundedButton'

interface Props extends DefaultProps {
  server: Server
  snapshots: Snapshot[]
}

const SnapshotRow = ({ snapshot }: { snapshot: Snapshot }) => {
  const [showDeleteModal, setShowDeleteModal] = useState(false)

  return (
    <>
      <Modal opened={showDeleteModal} onClose={() => setShowDeleteModal(false)} title={`Delete ${snapshot.name}?`} centered>

      </Modal>
      <tr key={snapshot.digest}>
        <td>{snapshot.name}</td>
        <td>{snapshot.running ? 'Yes' : 'No'}</td>
        <td>
          <RoundedButton onClick={() => setShowDeleteModal(true)}>
            <PlayIcon className='text-gray-600 w-5 h-5' />
          </RoundedButton>
          <RoundedButton onClick={() => setShowDeleteModal(true)}>
            <TrashIcon className='text-red-600 w-5 h-5' />
          </RoundedButton>
        </td>
      </tr>
    </>
  )
}

const Index = ({ auth, server, snapshots }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='h1'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - Snapshots`} />

      <Main>
        <div>
          <h3 className='h3-deemphasized'>Snapshots</h3>
          <Paper shadow='xs' className='p-card w-full mt-3'>
            <Table striped highlightOnHover>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Active</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {snapshots.map((snapshot) => (
                  <SnapshotRow snapshot={snapshot} />
                ))}
              </tbody>
            </Table>
          </Paper>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Index
