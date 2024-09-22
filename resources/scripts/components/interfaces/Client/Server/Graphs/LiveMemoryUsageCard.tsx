import byteSize from 'byte-size'
import { useEffect, useState } from 'react'
import { Area, AreaChart, CartesianGrid, YAxis } from 'recharts'

import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'
import useServerSWR from '@/api/servers/use-server-swr.ts'

import LiveIndicator from '@/components/interfaces/Client/Server/Graphs/LiveIndicator.tsx'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { ChartContainer } from '@/components/ui/Chart'


const YTickFormatter = (bytes: number) => {
    return byteSize(bytes, { units: 'iec', precision: 0 }).toString()
}

const LiveMemoryUsageCard = () => {
    const { data: server } = useServerSWR()
    const { data: state } = useServerStateSWR()
    const [data, setData] = useState(
        Array.from({ length: 10 }, () => ({ value: 0 }))
    )

    useEffect(() => {
        if (!state) return

        setData(prev => {
            const next = [...prev]
            next.shift()
            next.push({ value: state.memoryUsed })
            return next
        })
    }, [state])

    return (
        <Card className={'col-span-1 @md:col-span-2 @xl:col-span-1'}>
            <CardHeader>
                <CardTitle className={'flex items-center'}>
                    Live Memory Usage <LiveIndicator className={'ml-3'} />
                </CardTitle>
            </CardHeader>
            <CardContent>
                <ChartContainer config={{}} className='min-h-[12rem] w-full'>
                    <AreaChart accessibilityLayer data={data}>
                        <YAxis
                            tickCount={4}
                            tickFormatter={YTickFormatter}
                            domain={[0, server?.memory ?? 0]}
                            axisLine={false}
                            scale={'linear'}
                            width={64}
                            interval='preserveStartEnd'
                        />
                        <CartesianGrid vertical={false} />
                        <Area
                            isAnimationActive={false}
                            dataKey={'value'}
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

export default LiveMemoryUsageCard
