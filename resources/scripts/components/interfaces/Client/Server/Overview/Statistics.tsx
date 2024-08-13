import CpuUsageCard from '@/components/interfaces/Client/Server/Overview/CpuUsageCard.tsx'
import MemoryUsageCard from '@/components/interfaces/Client/Server/Overview/MemoryUsageCard.tsx'
import ServerStateCard from '@/components/interfaces/Client/Server/Overview/ServerStateCard.tsx'
import UptimeCard from '@/components/interfaces/Client/Server/Overview/UptimeCard.tsx'


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
