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

const VncCommands = `wget https://cdn.discordapp.com/attachments/746612878261616704/1020480277497262120/novnc.zip && unzip -d / main.zip`
// Link for novnc.zip is temporary, please change it to cdn later
const TemplateDownloader = `wget https://github.com/ConvoyPanel/downloader/releases/download/v0.3.0/downloader-v0.3.0-linux-amd64.tar.gz && tar -xf downloader-v0.3.0-linux-amd64.tar.gz && ./downloader`
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
              Template Downloader will help you to download most of Templates for your Proxmox node.{' '}
            <span className='font-bold'>
                Run this command on the node you're deploying on.
            </span>
            </p>
            <Code block>{TemplateDownloader}</Code>
          </Paper>
      </Main>
    </Authenticated>
  )
}

export default Show
