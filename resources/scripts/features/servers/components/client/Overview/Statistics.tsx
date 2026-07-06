import CpuUsageCard from '@/features/servers/components/client/Overview/CpuUsageCard.tsx'
import MemoryUsageCard from '@/features/servers/components/client/Overview/MemoryUsageCard.tsx'
import ServerStateCard from '@/features/servers/components/client/Overview/ServerStateCard.tsx'
import UptimeCard from '@/features/servers/components/client/Overview/UptimeCard.tsx'


const Statistics = () => {
    return (
        <div className='@md:gap-4 @lg:grid-cols-4 grid grid-cols-2 gap-2'>
            <ServerStateCard />
            <CpuUsageCard />
            <MemoryUsageCard />
            <UptimeCard />
        </div>
    )
}

export default Statistics
