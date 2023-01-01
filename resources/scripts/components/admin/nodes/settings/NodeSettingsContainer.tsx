import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import GeneralContainer from '@/components/admin/nodes/settings/GeneralContainer'

const NodeSettingsContainer = () => {
    return <NodeContentBlock title='Settings' showFlashKey='admin:node:settings'>
        <GeneralContainer />
    </NodeContentBlock>
}

export default NodeSettingsContainer
