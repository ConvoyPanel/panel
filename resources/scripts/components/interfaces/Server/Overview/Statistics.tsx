import {
    IconAirConditioningDisabled,
    IconCpu,
    IconDatabase,
    IconWifi,
} from '@tabler/icons-react'
import byteSize from 'byte-size'

import useServerSWR from '@/api/servers/use-server-swr.ts'

import StatisticCard from '@/components/interfaces/Server/Overview/StatisticCard.tsx'

import Progress from '@/components/ui/Progress.tsx'


const Statistics = () => {
    const { data: server } = useServerSWR()

    const memory = byteSize(server?.memory ?? 0, { units: 'iec', precision: 2 })
    const disk = byteSize(server?.disk ?? 0, { units: 'iec', precision: 2 })
    const bandwidthUsage = byteSize(server?.bandwidthUsage ?? 0, {
        units: 'iec',
        precision: 1,
    })
    const bandwidthLimit = byteSize(server?.bandwidthLimit ?? 0, {
        units: 'iec',
        precision: 1,
    })
    const bandwidthUsedPercent =
        server && server.bandwidthLimit
            ? (server.bandwidthUsage / server.bandwidthLimit) * 100
            : 0

    return (
        <div className='grid gap-4 md:grid-cols-2 lg:grid-cols-4'>
            <StatisticCard title={'CPU'} icon={IconCpu}>
                <p className={'text-2xl font-bold'}>{server?.cpu} Cores</p>
            </StatisticCard>
            <StatisticCard title={'Memory'} icon={IconAirConditioningDisabled}>
                <p className={'text-2xl font-bold'}>
                    {memory.value} {memory.unit}
                </p>
            </StatisticCard>
            <StatisticCard title={'Disk size'} icon={IconDatabase}>
                <p className={'text-2xl font-bold'}>
                    {disk.value} {disk.unit}
                </p>
            </StatisticCard>
            <StatisticCard
                className={'relative'}
                title={'Bandwidth Allowance'}
                icon={IconWifi}
            >
                <p className={'flex truncate'}>
                    <span className={'text-lg font-bold'}>
                        {bandwidthUsage.value} {bandwidthUsage.unit}
                    </span>
                    <span className={'ml-1.5 mt-1 text-xs'}>
                        / {bandwidthLimit.value} {bandwidthLimit.unit}
                    </span>
                </p>
                <Progress
                    className={'mt-2'}
                    value={bandwidthUsedPercent}
                    aria-label={`${bandwidthUsedPercent}% bandwidth used`}
                />
            </StatisticCard>
        </div>
    )
}

export default Statistics
