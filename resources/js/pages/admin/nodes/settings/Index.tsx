import { Node } from '@/api/admin/nodes/types'
import { DefaultProps } from '@/api/types/default'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import NodeNav from '@/components/nodes/NodeNav'
import { Head } from '@inertiajs/inertia-react'

interface Props extends DefaultProps {
    node: Node
}

const Index = ({ auth, node }: Props) => {

  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{ node.name }</h1>}
      secondaryHeader={<NodeNav id={node.id} />}
    >
      <Head title={`${node.name} - Settings`} />

      <Main>
      </Main>
    </Authenticated>
  )
}

export default Index
