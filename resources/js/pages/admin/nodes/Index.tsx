import React, { useMemo } from 'react'
import Authenticated from '@/components/layouts/Authenticated'
import { Head } from '@inertiajs/inertia-react'
import { DefaultProps } from '@/api/types/default'
import Main from '@/components/Main'
import { Button, Paper, Table } from '@mantine/core'
import { Node } from '@/api/nodes/types'
import EmptyState from '@/components/EmptyState'
import { ServerIcon } from '@heroicons/react/outline'
import { Inertia } from '@inertiajs/inertia'
import RoundedButton from '@/components/RoundedButton'
import { PencilIcon } from '@heroicons/react/solid'

interface Props extends DefaultProps {
  nodes: Node[]
}

export default function Index({ auth, nodes }: Props) {
  return (
    <Authenticated auth={auth} header={<h1 className='h1'>Nodes</h1>}>
      <Head title='Nodes' />

      <Main>
        <h2 className='h3-deemphasized'>Nodes</h2>
        <Paper shadow='xs' className='p-card w-full'>
          <div className='flex justify-end'>
            <Button onClick={() => Inertia.visit(route('admin.nodes.create'))}>
              Import Node
            </Button>
          </div>
          <Table className='mt-3' striped highlightOnHover>
            <thead>
              <tr>
                <th>Display Name</th>
                <th>Cluster</th>
                <th>Address</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {nodes.map((node) => (
                <tr>
                  <td>{node.name}</td>
                  <td>{node.cluster}</td>
                  <td>{`${node.hostname}:${node.port}`}</td>
                  <td>
                    <RoundedButton>
                      <PencilIcon className='text-gray-600 hover:text-blue-600 w-[18px] h-[18px]' />
                    </RoundedButton>
                  </td>
                </tr>
              ))}
            </tbody>
          </Table>

          {nodes.length === 0 && (
            <EmptyState
              icon={ServerIcon}
              title='No Nodes'
              description='Get started by importing a new node.'
              action='Import Node'
              onClick={() => Inertia.visit(route('admin.nodes.create'))}
            />
          )}
        </Paper>
      </Main>
    </Authenticated>
  )
}
