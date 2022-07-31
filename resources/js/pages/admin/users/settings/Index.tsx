import { AuthInterface, DefaultProps, User } from '@/api/types/default'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import UserNav from '@/components/users/UserNav'
import BasicSettings from '@/pages/admin/users/settings/modules/BasicSettings'
import DeleteSettings from '@/pages/admin/users/settings/modules/DeleteSettings'
import { Head } from '@inertiajs/inertia-react'
import { createContext } from 'react'

interface Props extends DefaultProps {
  user: User
}

export interface SettingsContextInterface {
  user: User
  auth: AuthInterface
}

export const SettingsContext = createContext<SettingsContextInterface | null>(
  null
)

const Index = ({ auth, user }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{user.name}</h1>}
      secondaryHeader={<UserNav id={user.id} />}
    >
      <Head title={`${user.name} - Settings`} />

      <Main>
        <h3 className='h3-deemphasized'>Settings</h3>
        <SettingsContext.Provider value={{ user, auth }}>
          <div className='settings-grid'>
            <div className='settings-column'>
                <BasicSettings />
            </div>
            <div className='settings-column'>
                <DeleteSettings />
            </div>
          </div>
        </SettingsContext.Provider>
      </Main>
    </Authenticated>
  )
}

export default Index
