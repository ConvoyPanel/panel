import BandwidthUsageCard from '@/features/servers/components/client/Overview/BandwidthUsageCard.tsx'
import CpuUsageCard from '@/features/servers/components/client/Overview/CpuUsageCard.tsx'
import MemoryUsageCard from '@/features/servers/components/client/Overview/MemoryUsageCard.tsx'
import ServerStateCard from '@/features/servers/components/client/Overview/ServerStateCard.tsx'
import StorageUsageCard from '@/features/servers/components/client/Overview/StorageUsageCard.tsx'

// Every reading the overview has, in one row-set. It used to be two grids of
// four, with uptime and the system specifications carrying figures that are
// now the denominators of the tiles above -- "of 1 vCPU", "of 1 GiB", the boot
// time behind "up 18 days". Five tiles instead of seven cards, and none of
// them ends in dead space.
//
// 4-up only at @5xl. These queries measure the whole content area (AppLayout's
// @container), not the card, so the threshold is a statement about the page.
// Measured: a 724px container gives 169px tiles, leaving ~113px of title, and
// "Memory Usage" wraps -- which drops that card's value below its neighbours'.
// 1024px gives 244px tiles, which every title clears.
//
// Bandwidth spans the full width at both counts: it is the one allowance
// measured in months rather than seconds, and its meter is the only figure
// here that reads better long.
const Statistics = () => {
    return (
        <div className='grid grid-cols-2 gap-2 @md:gap-4 @5xl:grid-cols-4'>
            <ServerStateCard />
            <CpuUsageCard />
            <MemoryUsageCard />
            <StorageUsageCard />
            <BandwidthUsageCard />
        </div>
    )
}

export default Statistics
