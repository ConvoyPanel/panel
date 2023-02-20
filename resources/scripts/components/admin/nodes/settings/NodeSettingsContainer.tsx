import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import DeleteNodeContainer from '@/components/admin/nodes/settings/DeleteNodeContainer'
import GeneralContainer from '@/components/admin/nodes/settings/GeneralContainer'
import FormSection from '@/components/elements/FormSection'
import SettingsLayout from '@/components/elements/layouts/SettingsLayout'

const NodeSettingsContainer = () => {
    return (
        // <NodeContentBlock title='Settings' showFlashKey='admin:node:settings'>
        //     <GeneralContainer />
        //     <FormSection.Divider />
        //     <DeleteNodeContainer />
        // </NodeContentBlock>
        <SettingsLayout
        indexPattern='/admin/nodes/:id/settings'
        defaultUrl='/admin/nodes/:id/settings/general'
        contentBlock={(props) => <NodeContentBlock title='Settings' showFlashKey='admin:node:settings' {...props}/>}
        routes={[
            {
                path: '/admin/nodes/:id/settings/general',
                name: 'General'
            },
        ]}
    />
    )
}

export default NodeSettingsContainer
