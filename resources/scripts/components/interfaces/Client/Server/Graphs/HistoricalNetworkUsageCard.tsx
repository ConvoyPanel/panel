import byteSize from 'byte-size'
import { useMemo } from 'react'
import { CartesianGrid, Line, LineChart, XAxis, YAxis } from 'recharts'

import useServerStatisticsSWR from '@/api/servers/use-server-statistics.ts'

import TimeRangeSelector from '@/components/interfaces/Client/Server/Graphs/TimeRangeSelector.tsx'
import useTimeRange from '@/components/interfaces/Client/Server/Graphs/use-time-range.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import {
    ChartConfig,
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/Chart'


const YTickFormatter = (bytes: number) => {
    return byteSize(bytes, { units: 'iec', precision: 0 }).toString()
}

const TooltipFormatter = (value: number) => {
    return byteSize(value, { units: 'iec', precision: 0 }).toString()
}

const config = {
    in: {
        label: 'In',
        color: 'hsl(var(--chart-4))',
    },
    out: {
        label: 'Out',
        color: 'hsl(var(--chart-5))',
    },
} satisfies ChartConfig

const HistoricalNetworkUsageCard = () => {
    const { from, setFrom, XTickFormatter } = useTimeRange()
    const { data } = useServerStatisticsSWR({
        from,
    })

    const usages = useMemo(() => {
        if (!data) return []

        return data.map(timepoint => ({
            in: timepoint.network.in,
            out: timepoint.network.out,
            timestamp: timepoint.timestamp,
        }))
    }, [data])

    return (
        <Card className={'col-span-1 @md:col-span-4 @lg:col-span-2'}>
            <CardHeader>
                <CardTitle className={'flex items-center'}>
                    Historical Network Usage
                </CardTitle>
            </CardHeader>
            <CardContent>
                <TimeRangeSelector from={from} setFrom={setFrom} />
                <ChartContainer
                    config={config}
                    className='min-h-[12rem] w-full'
                >
                    <LineChart accessibilityLayer data={usages}>
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
                            interval='preserveStartEnd'
                            scale={'linear'}
                        />
                        <ChartTooltip
                            cursor={false}
                            labelFormatter={(_label, payload) =>
                                payload[0].payload.timestamp.toLocaleString(
                                    [],
                                    {
                                        month: 'short',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    }
                                )
                            }
                            content={
                                <ChartTooltipContent
                                    styledFormatter={TooltipFormatter}
                                    indicator={'dot'}
                                />
                            }
                        />
                        <Line
                            type={'monotone'}
                            stroke={'hsl(var(--chart-2))'}
                            strokeWidth={2}
                            dot={false}
                            dataKey={'in'}
                        />
                        <Line
                            type={'monotone'}
                            stroke={'hsl(var(--chart-1))'}
                            strokeWidth={2}
                            dot={false}
                            dataKey={'out'}
                        />

                        <CartesianGrid vertical={false} />
                    </LineChart>
                </ChartContainer>
            </CardContent>
        </Card>
    )
}

export default HistoricalNetworkUsageCard
