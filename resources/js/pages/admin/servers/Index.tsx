import React, { useEffect, useMemo, useState } from 'react'
import Authenticated from '@/components/layouts/Authenticated'
import { Head } from '@inertiajs/inertia-react'
import { DefaultProps, PaginatedInterface } from '@/api/types/default'
import Main from '@/components/Main'
import { Pagination, Paper, Table } from '@mantine/core'
import { Button } from '@mantine/core'
import { Inertia } from '@inertiajs/inertia'
import { Server as DefaultServer } from '@/api/server/types'
import EditButton from '@/components/elements/tables/EditButton'
import EmptyState from '@/components/EmptyState'
import { ServerIcon } from '@heroicons/react/outline'
import { Template } from '@/api/admin/servers/templates/types'
import { CheckIcon, EyeIcon } from '@heroicons/react/solid'
import Paginator from '@/components/elements/pagination/Paginator'

interface Server extends DefaultServer {
  template?: Template
  owner: {
    id: number
    email: string
    name: string
  }
  node: {
    id: number
    name: string
  }
}

interface Props extends DefaultProps {
  servers: PaginatedInterface<Server[]>
}

const Index = ({ auth, servers }: Props) => {
  return (
    <Authenticated auth={auth} header={<h1 className='h1'>Servers</h1>}>
      <Head title='Servers' />

      <Main>
        <h2 className='h3-deemphasized'>Servers</h2>
        <Paper shadow='xs' className='p-card w-full'>
          <div className='flex justify-end'>
            <Button
              onClick={() => Inertia.visit(route('admin.servers.create'))}
            >
              New Server
            </Button>
          </div>

          <div className='overflow-auto'>
            <Table className='mt-3' striped highlightOnHover>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>VMID</th>
                  <th>Node</th>
                  <th>User</th>
                  <th>Template</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {servers.data.map((server) => (
                  <tr key={server.uuidShort}>
                    <td>{server.name}</td>
                    <td>{server.vmid}</td>
                    <td>{server.node.name}</td>
                    <td>{server.owner.email}</td>
                    <td>
                      {' '}
                      <div className='flex space-x-1'>
                        {' '}
                        {server.template && (
                          <CheckIcon className='text-green-600 w-[18px] h-[18px]' />
                        )}{' '}
                        {server?.template?.visible ? (
                          <EyeIcon className='text-blue-600 inline-block w-[18px] h-[18px]' />
                        ) : (
                          ''
                        )}
                      </div>
                    </td>
                    <td>
                      <EditButton
                        onClick={() =>
                          Inertia.visit(route('admin.servers.show', server.uuidShort))
                        }
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          </div>

          <Paginator pages={servers.meta.pagination.total_pages} route='admin.servers' />

          {servers.meta.pagination.total === 0 && (
            <EmptyState
              icon={ServerIcon}
              title='No Servers'
              description='Get started by creating a new server.'
              action='New Server'
              onClick={() => Inertia.visit(route('admin.servers.create'))}
            />
          )}
        </Paper>
      </Main>
    </Authenticated>
  )
}

export default Index
