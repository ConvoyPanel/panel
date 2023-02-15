import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import ServersTable from '@/components/admin/servers/ServersTable'
import { NodeContext } from '@/state/admin/node'
import { useState } from 'react'
import CreateServerModal from '@/components/admin/servers/CreateServerModal'
import SearchBar from '@/components/admin/SearchBar'
import { useDebouncedValue } from '@mantine/hooks'

const NodeServersContainer = () => {
    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const [open, setOpen] = useState(false)
    const node = NodeContext.useStoreState(state => state.node.data!)

    return (
        <div className='bg-background min-h-screen'>
            <CreateServerModal open={open} onClose={() => setOpen(false)} nodeId={node.id} />
            <NodeContentBlock title='Servers' showFlashKey='admin:node:servers'>
                <SearchBar value={query} onChange={e => setQuery(e.target.value)} buttonText='New Server' onClick={() => setOpen(true)} />
                <ServersTable query={debouncedQuery} nodeId={node.id} />
            </NodeContentBlock>
        </div>
    )
}

export default NodeServersContainer
