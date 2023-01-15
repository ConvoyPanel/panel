import ServerContentBlock from '@/components/admin/servers/ServerContentBlock'
import GeneralContainer from '@/components/admin/servers/settings/GeneralContainer'
import ServerBuildSettingsContainer from '@/components/admin/servers/settings/ServerBuildSettingsContainer'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import DangerZoneContainer from '@/components/admin/servers/settings/DangerZoneContainer'

const ServerSettingsContainer = () => {
    return (
        <ServerContentBlock title={'Settings'}>
            <GeneralContainer />
            <FormSection.Divider />
            <ServerBuildSettingsContainer />
            <FormSection.Divider />
            <DangerZoneContainer />
        </ServerContentBlock>
    )
}

export default ServerSettingsContainer
