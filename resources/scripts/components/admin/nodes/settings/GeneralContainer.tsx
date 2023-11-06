import CotermCard from '@/components/admin/nodes/settings/partials/general/CotermCard'
import DeleteNodeCard from '@/components/admin/nodes/settings/partials/general/DeleteNodeCard'
import NodeInformationCard from '@/components/admin/nodes/settings/partials/general/NodeInformationCard'

const GeneralContainer = () => {
    return (
        <>
            <NodeInformationCard />
            <CotermCard />
            <DeleteNodeCard />
        </>
    )
}

export default GeneralContainer