import { useMemo } from 'react'
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts'

import { useServerStatistics } from '@/features/servers/detail/api.ts'

import useTimeRange from '@/features/servers/hooks/use-time-range.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { ChartContainer } from '@/components/ui/Chart'

import TimeRangeSelector from './TimeRangeSelector'


const HistoricalCpuUsageCard = () => {
    const { from, setFrom, XTickFormatter } = useTimeRange()
    const { data } = useServerStatistics({
        from,
    })

    const usages = useMemo(() => {
        if (!data) return []

        return data.map(timepoint => ({
            usage: timepoint.cpuUsed * 100,
            timestamp: timepoint.timestamp,
        }))
    }, [data])

    return (
        <Card className={'col-span-1 @md:col-span-4 @lg:col-span-2'}>
            <CardHeader>
                <CardTitle className={'flex items-center'}>
                    Historical CPU Usage
                </CardTitle>
            </CardHeader>
            <CardContent>
                <TimeRangeSelector from={from} setFrom={setFrom} />
                <ChartContainer config={{}} className='min-h-[12rem] w-full'>
                    <AreaChart accessibilityLayer data={usages}>
                        <YAxis
                            ticks={[0, 25, 50, 75, 100]}
                            unit={'%'}
                            width={34}
                            axisLine={false}
                            interval='preserveStartEnd'
                        />
                        <XAxis
                            dataKey={'timestamp'}
                            height={from === 'hour' ? 50 : 85}
                            tickFormatter={XTickFormatter}
                            angle={-45}
                            textAnchor='end'
                        />
                        <CartesianGrid vertical={false} />

                        <Area
                            dataKey={'usage'}
                            type={'monotone'}
                            stroke={'none'}
                            fill='var(--chart-2)'
                        />
                    </AreaChart>
                </ChartContainer>
            </CardContent>
        </Card>
    )
}

export default HistoricalCpuUsageCard
