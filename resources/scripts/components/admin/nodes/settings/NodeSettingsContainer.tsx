import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import DeleteNodeContainer from '@/components/admin/nodes/settings/DeleteNodeContainer'
import GeneralContainer from '@/components/admin/nodes/settings/GeneralContainer'
import FormSection from '@/components/elements/FormSection'

const NodeSettingsContainer = () => {
    return (
        <NodeContentBlock title='Settings' showFlashKey='admin:node:settings'>
            <GeneralContainer />
            <FormSection.Divider />
            <DeleteNodeContainer />
        </NodeContentBlock>
    )
}

export default NodeSettingsContainer
