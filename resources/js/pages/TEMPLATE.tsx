import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import { Head } from '@inertiajs/inertia-react'

interface Props extends DefaultProps {
    server: Server
}

const Template = ({ auth}: Props) => {

    return <Authenticated
      auth={auth}
      header={
        <h1 className='h1'>
          template
        </h1>
      }
    >
      <Head title={`template`} />

      <Main>
      </Main>
    </Authenticated>
}

export default Template