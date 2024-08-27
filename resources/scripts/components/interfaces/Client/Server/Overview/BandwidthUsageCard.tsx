import { IconWifi } from '@tabler/icons-react'
import byteSize from 'byte-size'

import useServerSWR from '@/api/servers/use-server-swr.ts'

import StatisticCard from '@/components/interfaces/Client/Server/Overview/StatisticCard.tsx'

import Progress from '@/components/ui/Progress.tsx'


const BandwidthUsageCard = () => {
    const { data: server } = useServerSWR()

    const used = byteSize(server?.bandwidthUsage ?? 0, {
        units: 'iec',
        precision: 1,
    })
    const limit = byteSize(server?.bandwidthLimit ?? 0, {
        units: 'iec',
        precision: 1,
    })
    const bandwidthUsedPercent =
        server && server.bandwidthLimit
            ? (server.bandwidthUsage / server.bandwidthLimit) * 100
            : 0

    return (
        <StatisticCard
            title={'Bandwidth Allowance'}
            icon={IconWifi}
            className={'col-span-2 @sm:col-span-1'}
            footer={
                <Progress
                    value={bandwidthUsedPercent}
                    aria-label={`${bandwidthUsedPercent}% of your bandwidth allowance is used`}
                />
            }
        >
            <p>
                <span className={'text-lg font-bold @sm:text-xl @xl:text-2xl'}>
                    {used.value} {used.unit} used
                </span>
                <span className={'block text-sm text-muted-foreground'}>
                    out of {limit.value} {limit.unit} &#x2022;{' '}
                    {bandwidthUsedPercent.toFixed(2)}%
                </span>
            </p>
        </StatisticCard>
    )
}

export default BandwidthUsageCard
