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

const VncCommands = `curl -fsSL https://github.com/convoypanel/broker/releases/latest/download/broker.tar.gz | tar -xzv`
const TemplateCommands = `curl -fsSLo downloader https://github.com/convoypanel/downloader/releases/latest/download/downloader_linux_amd64
chmod +x ./downloader
./downloader`

const Show = ({ auth, node }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{node.name}</h1>}
      secondaryHeader={<NodeNav id={node.id} />}
    >
      <Head title={`${node.name} - Overview`} />

      <Main>
        <h3 className='h3-deemphasized'>Getting Started</h3>

        <Paper shadow='xs' className='p-card w-full space-y-3'>
          <h3 className='h3'>Configure VNC Service</h3>

          <p className='p-desc'>
            Install the noVNC broker to allow clients to connect their servers.
            Without this, clients will not be able to manage their servers from
            the web.{' '}
            <span className='font-bold'>
              Run this command on the node you're deploying on.
            </span>
          </p>
          <Code block>{VncCommands}</Code>
        </Paper>
        <Paper shadow='xs' className='p-card w-full space-y-3'>
          <h3 className='h3'>Add Server Templates</h3>

          <p className='p-desc'>
            Server templates are virtual machines preinstalled with some of the
            most popular systems, such as Windows, Ubuntu, Debian, etc. These
            templates are only compatible on amd64 systems. You also need a
            valid license from Performave to use these templates for production. {' '}
            <span className='font-bold'>
              Run this command on the node you're deploying on.
            </span>
          </p>

          <Code block>{TemplateCommands}</Code>
        </Paper>
      </Main>
    </Authenticated>
  )
}

export default Show
