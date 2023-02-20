import ServerContentBlock from '@/components/admin/servers/ServerContentBlock'
import SettingsLayout from '@/components/elements/layouts/SettingsLayout'

const ServerSettingsContainer = () => {
    return (
        <SettingsLayout
            indexPattern='/admin/servers/:id/settings'
            defaultUrl='/admin/servers/:id/settings/general'
            contentBlock={(props) => <ServerContentBlock title='Settings' {...props}/>}
            routes={[
                {
                    path: '/admin/servers/:id/settings/general',
                    name: 'General'
                },
                {
                    path: '/admin/servers/:id/settings/hardware',
                    name: 'Hardware'
                },
            ]}
        />
    )
}

export default ServerSettingsContainer
