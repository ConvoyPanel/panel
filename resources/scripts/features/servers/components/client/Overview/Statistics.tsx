import CpuUsageCard from '@/features/servers/components/client/Overview/CpuUsageCard.tsx'
import MemoryUsageCard from '@/features/servers/components/client/Overview/MemoryUsageCard.tsx'
import ServerStateCard from '@/features/servers/components/client/Overview/ServerStateCard.tsx'
import UptimeCard from '@/features/servers/components/client/Overview/UptimeCard.tsx'

// 4-up only at @5xl. These queries measure the whole content area (AppLayout's
// @container), not the card, so the threshold is a statement about the page.
// Measured: a 724px container gives 169px tiles, leaving ~113px of title, and
// "Memory Usage" wraps -- which drops that card's value below its neighbours'.
// 1024px gives 244px tiles, which every title clears.
const Statistics = () => {
    return (
        <div className='grid grid-cols-2 gap-2 @md:gap-4 @5xl:grid-cols-4'>
            <ServerStateCard />
            <CpuUsageCard />
            <MemoryUsageCard />
            <UptimeCard />
        </div>
    )
}

export default Statistics
