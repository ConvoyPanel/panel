import PageContentBlock from '@/components/elements/PageContentBlock'
import SettingsLayout from '@/components/elements/layouts/SettingsLayout'

const SettingsContainer = () => (
    <SettingsLayout
        indexPattern='/admin/settings'
        defaultUrl='/admin/settings/updates'
        contentBlock={props => (
            <PageContentBlock
                title='Settings'
                showFlashKey='admin:settings'
                {...props}
            />
        )}
        routes={[
            { path: '/admin/settings/updates', name: 'Updates' },
            { path: '/admin/settings/telemetry', name: 'Telemetry' },
            { path: '/admin/settings/upgrade', name: 'Upgrade to Convoy 6' },
        ]}
    />
)

export default SettingsContainer
