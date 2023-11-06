import { useTranslation } from 'react-i18next'

import SettingsLayout from '@/components/elements/layouts/SettingsLayout'

import ServerContentBlock from '@/components/admin/servers/ServerContentBlock'


const ServerSettingsContainer = () => {
    const { t: tStrings } = useTranslation('strings')

    return (
        <SettingsLayout
            indexPattern='/admin/servers/:id/settings'
            defaultUrl='/admin/servers/:id/settings/general'
            contentBlock={props => (
                <ServerContentBlock
                    title={tStrings('setting_other')}
                    {...props}
                />
            )}
            routes={[
                {
                    path: '/admin/servers/:id/settings/general',
                    name: tStrings('general'),
                },
                {
                    path: '/admin/servers/:id/settings/hardware',
                    name: tStrings('hardware'),
                },
            ]}
        />
    )
}

export default ServerSettingsContainer