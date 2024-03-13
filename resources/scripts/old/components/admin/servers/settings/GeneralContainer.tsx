import DeleteServerCard from '@/components/admin/servers/settings/partials/general/DeleteServerCard'
import ServerInformationCard from '@/components/admin/servers/settings/partials/general/ServerInformationCard'
import ServerSuspensionCard from '@/components/admin/servers/settings/partials/general/ServerSuspensionCard'

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