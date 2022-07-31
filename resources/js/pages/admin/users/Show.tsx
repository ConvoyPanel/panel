import { Node } from '@/api/admin/nodes/types'
import { DefaultProps, User } from '@/api/types/default'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import { Head } from '@inertiajs/inertia-react'
import UserNav from '@/components/users/UserNav'

interface Props extends DefaultProps {
    user: User
}

const Show = ({ auth, user }: Props) => {

  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{ user.name }</h1>}
      secondaryHeader={<UserNav id={user.id} />}
    >
      <Head title={`${user.name} - Overview`} />

      <Main>
        <h3 className='h3-deemphasized'>Overview</h3>
      </Main>
    </Authenticated>
  )
}

export default Show
