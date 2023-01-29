import ServerContentBlock from '@/components/servers/ServerContentBlock'
import SettingsLayout from '@/components/elements/layouts/SettingsLayout'

const ServerSettingsContainer = () => {
    return (
        <SettingsLayout
            indexPattern='/servers/:id/settings'
            defaultUrl='/servers/:id/settings/general'
            contentBlock={(props) => <ServerContentBlock title='Server Settings' showFlashKey='server:settings' {...props}/>}
            routes={[
                {
                    path: '/servers/:id/settings/general',
                    name: 'General'
                },
                {
                    path: '/servers/:id/settings/hardware',
                    name: 'Hardware'
                },
                {
                    path: '/servers/:id/settings/network',
                    name: 'Network'
                },
                {
                    path: '/servers/:id/settings/security',
                    name: 'Security'
                },
            ]}
        />
    )
}

export default ServerSettingsContainer
