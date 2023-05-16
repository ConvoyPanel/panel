import ServerInformationCard from '@/components/admin/servers/settings/partials/general/ServerInformationCard'
import ServerSuspensionCard from '@/components/admin/servers/settings/partials/general/ServerSuspensionCard'
import DeleteServerCard from '@/components/admin/servers/settings/partials/general/DeleteServerCard'

const GeneralContainer = () => {
    return (
        <>
            <ServerInformationCard />
            <ServerSuspensionCard />
            <DeleteServerCard />
        </>
    )
}

export default GeneralContainer
