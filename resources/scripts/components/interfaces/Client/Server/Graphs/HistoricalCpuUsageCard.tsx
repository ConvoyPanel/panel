import { ServerTimepointData } from '@/types/server.ts'
import { useCallback, useMemo, useState } from 'react'
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts'

import { TimeRange } from '@/api/servers/getStatistics.ts'
import useServerStatisticsSWR from '@/api/servers/use-server-statistics.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { ChartContainer } from '@/components/ui/Chart'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/Tabs'


const HistoricalCpuUsageCard = () => {
    const [from, setFrom] = useState<TimeRange>('hour')
    const { data } = useServerStatisticsSWR({
        from,
    })

    const usages = useMemo(() => {
        if (!data) return []

        return data.map((timepoint: ServerTimepointData) => ({
            usage: timepoint.cpuUsed * 100,
            timestamp: timepoint.timestamp,
        }))
    }, [data])

    // todo: make a custom tick component instead of this
    const tickFormatter = useCallback(
        (date: Date) => {
            const time = date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
            })

            if (from === 'hour') {
                return time
            }

            const formattedDate = date.toLocaleDateString([], {
                month: 'short',
                day: 'numeric',
            })

            return `${formattedDate} ${time}`
        },
        [from]
    )

    return (
        <Card className={'col-span-1 @md:col-span-4 @lg:col-span-2'}>
            <CardHeader>
                <CardTitle className={'flex items-center'}>
                    Historical CPU Usage
                </CardTitle>
            </CardHeader>
            <CardContent>
                <Tabs
                    value={from}
                    onValueChange={val => setFrom(val as TimeRange)}
                    className={'mb-4'}
                >
                    <TabsList>
                        <TabsTrigger value='hour'>Hourly</TabsTrigger>
                        <TabsTrigger value='day'>Daily</TabsTrigger>
                        <TabsTrigger value='week'>Weekly</TabsTrigger>
                        <TabsTrigger value='month'>Monthly</TabsTrigger>
                        <TabsTrigger value='year'>Yearly</TabsTrigger>
                    </TabsList>
                </Tabs>
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
                            tickFormatter={tickFormatter}
                            angle={-45}
                            textAnchor='end'
                        />
                        <CartesianGrid vertical={false} />

                        <Area
                            dataKey={'usage'}
                            type={'monotone'}
                            stroke={'none'}
                            fill='#3b82f6'
                        />
                    </AreaChart>
                </ChartContainer>
            </CardContent>
        </Card>
    )
}

export default HistoricalCpuUsageCard
