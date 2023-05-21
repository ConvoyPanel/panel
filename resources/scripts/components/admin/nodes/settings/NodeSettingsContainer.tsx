import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import SettingsLayout from '@/components/elements/layouts/SettingsLayout'
import { useTranslation } from 'react-i18next'

const NodeSettingsContainer = () => {
    const { t: tStrings } = useTranslation('strings')

    return (
        <SettingsLayout
            indexPattern='/admin/nodes/:id/settings'
            defaultUrl='/admin/nodes/:id/settings/general'
            contentBlock={props => <NodeContentBlock title={tStrings('setting_other')} {...props} />}
            routes={[
                {
                    path: '/admin/nodes/:id/settings/general',
                    name: tStrings('general'),
                },
            ]}
        />
    )
}

export default NodeSettingsContainer
