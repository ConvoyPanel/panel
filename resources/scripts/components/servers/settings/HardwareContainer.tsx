import MediaCard from '@/components/servers/settings/partials/hardware/MediaCard'
import BootOrderCard from '@/components/servers/settings/partials/hardware/BootOrderCard'
import HardwareDetailsCard from '@/components/servers/settings/partials/hardware/HardwareDetailsCard'

const HardwareContainer = () => (
    <>
        <HardwareDetailsCard />
        <MediaCard />
        <BootOrderCard />
    </>
)

export default HardwareContainer
