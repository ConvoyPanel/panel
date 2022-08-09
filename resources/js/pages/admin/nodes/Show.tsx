import { Node } from '@/api/admin/nodes/types'
import { DefaultProps } from '@/api/types/default'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import NodeNav from '@/components/nodes/NodeNav'
import { Head } from '@inertiajs/inertia-react'
import { Code, Paper } from '@mantine/core'

interface Props extends DefaultProps {
    node: Node
}

const VncCommands = `wget -qO- https://github.com/Performave/convoy-novnc/archive/refs/heads/main.zip | unzip`

const Show = ({ auth, node }: Props) => {

  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{ node.name }</h1>}
      secondaryHeader={<NodeNav id={node.id} />}
    >
      <Head title={`${node.name} - Overview`} />

      <Main>
        <h3 className='h3-deemphasized'>Getting Started</h3>

        <Paper shadow='xs' className='p-card w-full space-y-3'>
            <h3 className='h3'>Configure VNC Service</h3>

            <p className='p-desc'>
              Install the noVNC broker to allow clients to connect their
              servers. Without this, clients will not be able to manage their
              servers from the web.{' '}
              <span className='font-bold'>
                Run this command on the node you're deploying on.
              </span>
            </p>
            <Code block>{VncCommands}</Code>
          </Paper>
          <Paper shadow='xs' className='p-card w-full space-y-3'>
            <h3 className='h3'>Add Server Templates</h3>

            <p className='p-desc'>
              Server templates are unavailable at the moment.
            </p>
          </Paper>
      </Main>
    </Authenticated>
  )
}

export default Show
