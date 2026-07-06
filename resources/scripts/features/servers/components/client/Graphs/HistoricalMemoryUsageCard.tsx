import byteSize from 'byte-size'
import { useMemo } from 'react'
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts'

import {
    useServerStatistics,
    useServer,
} from '@/features/servers/detail/api.ts'

import TimeRangeSelector from '@/features/servers/components/client/Graphs/TimeRangeSelector.tsx'
import useTimeRange from '@/features/servers/hooks/use-time-range.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { ChartContainer } from '@/components/ui/Chart'


const YTickFormatter = (bytes: number) => {
    return byteSize(bytes, { units: 'iec', precision: 0 }).toString()
}

const HistoricalMemoryUsageCard = () => {
    const { from, setFrom, XTickFormatter } = useTimeRange()
    const { data } = useServerStatistics({
        from,
    })
    const { data: server } = useServer()

    const usages = useMemo(() => {
        if (!data) return []

        return data.map(timepoint => ({
            usage: timepoint.memoryUsed,
            timestamp: timepoint.timestamp,
        }))
    }, [data])

    return (
        <Card className={'col-span-1 @md:col-span-4 @lg:col-span-2'}>
            <CardHeader>
                <CardTitle className={'flex items-center'}>
                    Historical Memory Usage
                </CardTitle>
            </CardHeader>
            <CardContent>
                <TimeRangeSelector from={from} setFrom={setFrom} />
                <ChartContainer config={{}} className='min-h-[12rem] w-full'>
                    <AreaChart accessibilityLayer data={usages}>
                        <XAxis
                            dataKey={'timestamp'}
                            height={from === 'hour' ? 50 : 85}
                            tickFormatter={XTickFormatter}
                            angle={-45}
                            textAnchor='end'
                        />
                        <YAxis
                            tickFormatter={YTickFormatter}
                            width={64}
                            axisLine={false}
                            minTickGap={0}
                            scale={'linear'}
                            interval='preserveStartEnd'
                            domain={[0, server?.memory ?? 0]}
                        />
                        <CartesianGrid vertical={false} />

                        <Area
                            dataKey={'usage'}
                            type={'monotone'}
                            stroke={'none'}
                            fill='hsl(var(--chart-1))'
                        />
                    </AreaChart>
                </ChartContainer>
            </CardContent>
        </Card>
    )
}

export default HistoricalMemoryUsageCard
