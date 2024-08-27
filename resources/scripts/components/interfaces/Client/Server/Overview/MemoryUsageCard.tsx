import { IconAirConditioningDisabled } from '@tabler/icons-react'
import byteSize from 'byte-size'

import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'

import StatisticCard from '@/components/interfaces/Client/Server/Overview/StatisticCard.tsx'

import Skeleton from '@/components/ui/Skeleton.tsx'


const MemoryUsageCard = () => {
    const { data: state } = useServerStateSWR()

    const used = byteSize(state?.memoryUsed ?? 0, {
        units: 'iec',
        precision: 2,
    })
    const total = byteSize(state?.memoryTotal ?? 0, {
        units: 'iec',
        precision: 2,
    })

    return (
        <StatisticCard
            title={'Memory Usage'}
            icon={IconAirConditioningDisabled}
        >
            {state ? (
                <p className={'relative'}>
                    <span
                        className={
                            'inline-block text-lg font-bold @xl:text-2xl'
                        }
                    >
                        {used.value} {used.unit}
                    </span>
                    <span
                        className={
                            'absolute -bottom-2.5 right-0 ml-1.5 text-xs text-muted-foreground @sm:bottom-auto @sm:right-auto @sm:mt-1'
                        }
                    >
                        / {total.value} {total.unit}
                    </span>
                </p>
            ) : (
                <Skeleton className={'h-7 w-full @sm:h-8'} />
            )}
        </StatisticCard>
    )
}

export default MemoryUsageCard
