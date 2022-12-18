import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import NodeDetailsBlock from '@/components/admin/nodes/overview/NodeDetailsBlock'
import PageContentBlock from '@/components/elements/PageContentBlock'

const NodeOverviewContainer = () => {
    return (
        <NodeContentBlock title='Overview'>
            <div className='grid grid-cols-10 gap-6'>
            <NodeDetailsBlock />
                </div>
        </NodeContentBlock>
    )
}

export default NodeOverviewContainer
