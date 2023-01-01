import FormSection from '@/components/elements/FormSection'
import ServerContentBlock from '@/components/servers/ServerContentBlock'
import GeneralContainer from '@/components/servers/settings/GeneralContainer'
import HardwareContainer from '@/components/servers/settings/HardwareContainer'
import NetworkingContainer from '@/components/servers/settings/NetworkingContainer'
import SecurityContainer from '@/components/servers/settings/SecurityContainer'

const ServerSettingsContainer = () => {
    return (
        <ServerContentBlock title='Settings' showFlashKey='settings'>
            <GeneralContainer />
            <FormSection.Divider />
            <HardwareContainer />
            <FormSection.Divider />
            <SecurityContainer />
            <FormSection.Divider />
            <NetworkingContainer />
        </ServerContentBlock>
    )
}

export default ServerSettingsContainer
