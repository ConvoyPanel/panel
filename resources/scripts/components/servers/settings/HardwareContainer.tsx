import BootOrderCard from '@/components/servers/settings/partials/hardware/BootOrderCard'
import HardwareDetailsCard from '@/components/servers/settings/partials/hardware/HardwareDetailsCard'
import IsoCard from '@/components/servers/settings/partials/hardware/IsoCard'

const HardwareContainer = () => (
    <>
        <HardwareDetailsCard />
        <IsoCard />
        <BootOrderCard />
    </>
)

export default HardwareContainer