import ServerContentBlock from '@/components/admin/servers/ServerContentBlock'
import GeneralContainer from '@/components/admin/servers/settings/GeneralContainer'
import ServerBuildSettingsContainer from '@/components/admin/servers/settings/ServerBuildSettingsContainer'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'

const ServerSettingsContainer = () => {
    return (
        <ServerContentBlock title={'Settings'}>
            <GeneralContainer />
            <FormSection.Divider />
            <ServerBuildSettingsContainer />
        </ServerContentBlock>
    )
}

export default ServerSettingsContainer
