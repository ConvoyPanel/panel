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
// Two-up, then five-up in one step at @5xl. These queries measure the whole
// content area (AppLayout's @container), not the card, so the threshold is a
// statement about the page.
//
// There is no four-up rung in between. Four columns leave bandwidth spanning a
// whole row on its own, and at a 1440px laptop -- squarely inside the band a
// four-up rung would own -- that is a 1090px card holding "0 B" and a bar the
// width of the page. Five columns at 1024px make each tile ~180px, which is
// tight: it is what the shorter context lines in ServerStateCard and
// BandwidthUsageCard are cut to clear.
const Statistics = () => {
    return (
        <div className='grid grid-cols-2 gap-2 @md:gap-4 @5xl:grid-cols-5'>
            <ServerStateCard />
            <CpuUsageCard />
            <MemoryUsageCard />
            <StorageUsageCard />
            <BandwidthUsageCard />
        </div>
    )
}

export default Statistics
