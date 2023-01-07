import ServerContentBlock from '@/components/admin/servers/ServerContentBlock'
import GeneralContainer from '@/components/admin/servers/settings/GeneralContainer'

const ServerSettingsContainer = () => {
    return (
        <ServerContentBlock title={'Settings'}>
            <GeneralContainer />
        </ServerContentBlock>
    )
}

export default ServerSettingsContainer
