import { useTranslation } from 'react-i18next'

import SettingsLayout from '@/components/elements/layouts/SettingsLayout'

import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'


const NodeSettingsContainer = () => {
    const { t: tStrings } = useTranslation('strings')

    return (
        <SettingsLayout
            indexPattern='/admin/nodes/:nodeId/settings'
            defaultUrl='/admin/nodes/:nodeId/settings/general'
            contentBlock={props => (
                <NodeContentBlock
                    title={tStrings('setting_other')}
                    {...props}
                />
            )}
            routes={[
                {
                    path: '/admin/nodes/:nodeId/settings/general',
                    name: tStrings('general'),
                },
            ]}
        />
    )
}

export default NodeSettingsContainer