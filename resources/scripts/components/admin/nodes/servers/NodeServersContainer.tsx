import { useDebouncedValue } from '@mantine/hooks'
import { useState } from 'react'

import useNodeSWR from '@/api/admin/nodes/useNodeSWR'

import SearchBar from '@/components/admin/SearchBar'
import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import CreateServerModal from '@/components/admin/servers/CreateServerModal'
import ServersTable from '@/components/admin/servers/ServersTable'


const NodeServersContainer = () => {
    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const [open, setOpen] = useState(false)
    const { data: node } = useNodeSWR()

    return (
        <div className='bg-background min-h-screen'>
            <CreateServerModal
                open={open}
                onClose={() => setOpen(false)}
                nodeId={node.id}
            />
            <NodeContentBlock title='Servers' showFlashKey='admin:node:servers'>
                <SearchBar
                    value={query}
                    onChange={e => setQuery(e.target.value)}
                    buttonText='New Server'
                    onClick={() => setOpen(true)}
                />
                <ServersTable query={debouncedQuery} nodeId={node.id} />
            </NodeContentBlock>
        </div>
    )
}

export default NodeServersContainer