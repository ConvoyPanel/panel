import { IconDatabase } from '@tabler/icons-react'
import byteSize from 'byte-size'

import useServerSWR from '@/api/servers/use-server-swr.ts'

import LinearProgressBar from '@/components/ui/Progress/LinearProgressBar.tsx'

import StatisticCard from './StatisticCard'


const StorageUsageCard = () => {
    const { data: server } = useServerSWR()

    const used = byteSize(8465553345, {
        units: 'iec',
        precision: 2,
    })
    const total = byteSize(server?.disk ?? 0, {
        units: 'iec',
        precision: 2,
    })

    const usedPercent = server ? (8465553345 / server.disk) * 100 : 0

    return (
        <StatisticCard
            title={'Storage Usage'}
            icon={IconDatabase}
            className={'col-span-2 @sm:col-span-1'}
            footer={
                <LinearProgressBar
                    className={'bottom-0'}
                    value={usedPercent}
                    aria-label={`${usedPercent}% of your storage is used`}
                />
            }
        >
            <p>
                <span className={'text-lg font-bold @sm:text-xl @xl:text-2xl'}>
                    {used.value} {used.unit} used
                </span>
                <span className={'block text-sm text-muted-foreground'}>
                    out of {total.value} {total.unit} &#x2022;{' '}
                    {usedPercent.toFixed(2)}%
                </span>
            </p>
        </StatisticCard>
    )
}

export default StorageUsageCard
