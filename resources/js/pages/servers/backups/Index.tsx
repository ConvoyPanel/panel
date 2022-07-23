import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerNav from '@/components/servers/ServerNav'
import { Head } from '@inertiajs/inertia-react'
import { Button, Paper, Table } from '@mantine/core'
import { PlusIcon } from '@heroicons/react/solid'
import { Backup } from '@/api/server/backups/types'
import { DuplicateIcon } from '@heroicons/react/outline'

interface Props extends DefaultProps {
  server: Server
  backups: Backup[]
}

const Index = ({ auth, server, backups }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - Backups`} />

      <Main>

        <div>
          <h3 className='h3-deemphasized'>Backups</h3>
          <Paper shadow='xs' className='p-card w-full mt-3'>
            <div className='flex justify-end'>
              <Button>
                New Backup
              </Button>
            </div>
            <Table striped highlightOnHover>
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
              </tbody>
            </Table>

            {backups.length <= 1 && (
              <div className='flex flex-col justify-center items-center text-center h-[60vh]'>
                <DuplicateIcon
                  className='mx-auto h-12 w-12 text-gray-400'
                  aria-hidden='true'
                />
                <h3 className='mt-2 text-sm font-medium text-gray-900'>
                  No Backups
                </h3>
                <p className='mt-1 text-sm text-gray-500'>
                  Get started by creating a new backup.
                </p>
                <div className='mt-6'>
                  <Button type='button'>
                    <PlusIcon
                      className='-ml-1 mr-2 h-5 w-5'
                      aria-hidden='true'
                    />
                    New Backup
                  </Button>
                </div>
              </div>
            )}
          </Paper>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Index
