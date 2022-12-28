import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import ServersTable from '@/components/admin/servers/ServersTable'
import Button from '@/components/elements/Button'
import { NodeContext } from '@/state/admin/node'
import { useState } from 'react'

const NodeServersContainer = () => {
    const [open, setOpen] = useState(false)
    const node = NodeContext.useStoreState(state => state.node.data!)

    return (
        <div className='bg-background min-h-screen'>
            <NodeContentBlock title='Servers' showFlashKey='admin:node:servers'>
                <div className='flex justify-end items-center mb-3'>
                    <Button onClick={() => setOpen(true)} variant='filled'>
                        New Server
                    </Button>
                </div>
                <ServersTable nodeId={node.id} />
            </NodeContentBlock>
        </div>
    )
}

export default NodeServersContainer
