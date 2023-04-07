import IsoCard from '@/components/servers/settings/partials/hardware/IsoCard'
import BootOrderCard from '@/components/servers/settings/partials/hardware/BootOrderCard'
import HardwareDetailsCard from '@/components/servers/settings/partials/hardware/HardwareDetailsCard'

const HardwareContainer = () => (
    <>
        <HardwareDetailsCard />
        <IsoCard />
        <BootOrderCard />
    </>
)

export default HardwareContainer
