import { Node } from '@/api/admin/nodes/types'
import { AuthInterface, DefaultProps } from '@/api/types/default'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import NodeNav from '@/components/nodes/NodeNav'
import BasicSettings from '@/pages/admin/nodes/settings/modules/BasicSettings'
import DeleteSettings from '@/pages/admin/nodes/settings/modules/DeleteSettings'
import { Head } from '@inertiajs/inertia-react'
import { createContext } from 'react'

interface Props extends DefaultProps {
  node: Node
}

export interface SettingsContextInterface {
  node: Node
  auth: AuthInterface
}

export const SettingsContext = createContext<SettingsContextInterface | null>(
  null
)

const Index = ({ auth, node }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{node.name}</h1>}
      secondaryHeader={<NodeNav id={node.id} />}
    >
      <Head title={`${node.name} - Settings`} />

      <Main>
        <h3 className='h3-deemphasized'>Settings</h3>
        <SettingsContext.Provider value={{ node, auth }}>
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
