import React, { useMemo, useState } from 'react'
import Authenticated from '@/components/layouts/Authenticated'
import { Head } from '@inertiajs/inertia-react'
import { DefaultProps, PaginatedInterface } from '@/api/types/default'
import Main from '@/components/Main'
import { Button, Paper, Table } from '@mantine/core'
import { Inertia } from '@inertiajs/inertia'
import EmptyState from '@/components/EmptyState'
import { User } from '@/api/types/default'
import { CheckIcon } from '@heroicons/react/solid'
import EditButton from '@/components/elements/tables/EditButton'
import { UserCircleIcon } from '@heroicons/react/outline'
import NewUserModal from '@/components/users/NewUserModal'
import Paginator from '@/components/elements/pagination/Paginator'

interface Props extends DefaultProps {
  users: PaginatedInterface<User[]>
}

export default function Index({ auth, users }: Props) {
  const [open, setOpen] = useState(false)

  return (
    <Authenticated auth={auth} header={<h1 className='h1'>Users</h1>}>
      <Head title='Users' />

      <Main>
        <NewUserModal open={open} setOpen={setOpen} />
        <h2 className='h3-deemphasized'>Users</h2>
        <Paper shadow='xs' className='p-card w-full'>
          <div className='flex justify-end'>
            <Button
              onClick={() => setOpen(true)}
            >
              New User
            </Button>
          </div>

          <div className='overflow-auto'>
            <Table className='mt-3' striped highlightOnHover>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Admin</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {users.data.map((user) => (
                  <tr key={user.id}>
                    <td>{user.name}</td>
                    <td>{user.email}</td>
                    <td>
                      {user.root_admin ? (
                        <CheckIcon className='text-green-600 w-[18px] h-[18px]' />
                      ) : (
                        ''
                      )}
                    </td>
                    <td>
                      <EditButton
                        onClick={() =>
                          Inertia.visit(route('admin.users.show', user.id))
                        }
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          </div>

          <Paginator pages={users.meta.pagination.total_pages} route='admin.users' />

          {users.meta.pagination.total === 0 && (
            <EmptyState
              icon={UserCircleIcon}
              title='No Users'
              description='Wait. How is this possible? You must be a wizard!'
              action='New User'
              onClick={() => setOpen(true)}
            />
          )}
        </Paper>
      </Main>
    </Authenticated>
  )
}
