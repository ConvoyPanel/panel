import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'
import { useServer } from '@/features/servers/detail/api.ts'
import { IconWifi } from '@tabler/icons-react'
import byteSize from 'byte-size'

import LinearProgressBar from '@/components/ui/Progress/LinearProgressBar.tsx'

const BandwidthUsageCard = () => {
    const { data: server } = useServer()

    const used = byteSize(server?.bandwidth.usage ?? 0, {
        units: 'iec',
        precision: 1,
    })
    const isUnlimited = server?.bandwidth.limit === -1
    const limit = byteSize(!isUnlimited ? (server?.bandwidth.limit ?? 0) : 0, {
        units: 'iec',
        precision: 1,
    })
    const bandwidthUsedPercent =
        server && server.bandwidth.limit > 0
            ? (server.bandwidth.usage / server.bandwidth.limit) * 100
            : 0

    return (
        <StatisticCard
            title={'Bandwidth Allowance'}
            icon={IconWifi}
            className={'col-span-2 @md:col-span-1'}
            meter={
                !isUnlimited && (
                    <LinearProgressBar
                        value={bandwidthUsedPercent}
                        aria-label={`${bandwidthUsedPercent.toFixed(2)}% of your bandwidth allowance is used`}
                    />
                )
            }
        >
            <p>
                <span
                    className={
                        'text-lg font-semibold tracking-tight @sm:text-xl @xl:text-2xl'
                    }
                >
                    {used.value} {used.unit}
                </span>
                <span className={'text-muted-foreground block text-sm'}>
                    {isUnlimited ? (
                        'used of an unlimited allowance'
                    ) : (
                        <>
                            used of {limit.value} {limit.unit} &#x2022;{' '}
                            {bandwidthUsedPercent.toFixed(0)}%
                        </>
                    )}
                </span>
            </p>
        </StatisticCard>
    )
}

export default BandwidthUsageCard
