import React from 'react'
import Authenticated from '@/components/layouts/Authenticated'
import { Head } from '@inertiajs/inertia-react'
import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/types/server'
import Main from '@/components/Main'

interface Props extends DefaultProps {
  servers: Server[]
}

export default function Dashboard({ auth, servers }: Props) {
  return (
    <Authenticated
      auth={auth}
      header={
        <h2 className='font-semibold text-xl text-gray-800 leading-tight'>
          Dashboard
        </h2>
      }
    >
      <Head title='Dashboard' />

      <Main>
        <div className='bg-white overflow-hidden shadow-sm sm:rounded-lg'>
          <div className='p-6 bg-white border-b border-gray-200'>
            You're logged in!
          </div>
        </div>
      </Main>
    </Authenticated>
  )
}
