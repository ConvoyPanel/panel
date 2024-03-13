import SettingsLayout from '@/components/elements/layouts/SettingsLayout'

import ServerContentBlock from '@/components/servers/ServerContentBlock'


const ServerSettingsContainer = () => {
    return (
        <SettingsLayout
            indexPattern='/servers/:id/settings'
            defaultUrl='/servers/:id/settings/general'
            contentBlock={props => (
                <ServerContentBlock
                    title='Server Settings'
                    showFlashKey='server:settings'
                    {...props}
                />
            )}
            routes={[
                {
                    path: '/servers/:id/settings/general',
                    name: 'General',
                },
                {
                    path: '/servers/:id/settings/hardware',
                    name: 'Hardware',
                },
                {
                    path: '/servers/:id/settings/network',
                    name: 'Network',
                },
                {
                    path: '/servers/:id/settings/security',
                    name: 'Security',
                },
            ]}
        />
    )
}

export default ServerSettingsContainer