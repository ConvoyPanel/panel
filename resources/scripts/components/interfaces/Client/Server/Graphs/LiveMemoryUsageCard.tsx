import { oldFormatBytes } from '@/utils'
import { useEffect, useMemo, useState } from 'react'
import { Area, AreaChart, CartesianGrid, YAxis } from 'recharts'

import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'
import useServerSWR from '@/api/servers/use-server-swr.ts'

import LiveIndicator from '@/components/interfaces/Client/Server/Graphs/LiveIndicator.tsx'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { ChartContainer } from '@/components/ui/Chart'


const LiveMemoryUsageCard = () => {
    const { data: server } = useServerSWR()
    const { data: state } = useServerStateSWR()
    const [data, setData] = useState(
        Array.from({ length: 10 }, () => ({ value: 0 }))
    )

    const total = useMemo(
        () => oldFormatBytes(server?.memory ?? 0, 2),
        [server?.memory]
    )

    useEffect(() => {
        if (!state) return

        setData(prev => {
            const next = [...prev]
            next.shift()
            // @ts-ignore
            const used = oldFormatBytes(state.memoryUsed, 2, total.unit)
            next.push({ value: used.size })
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
                            ticks={[0, total.size / 2, total.size]}
                            unit={` ${total.unit}`}
                            axisLine={false}
                            width={50}
                            interval='preserveStartEnd'
                        />
                        <CartesianGrid vertical={false} />
                        <Area
                            isAnimationActive={false}
                            dataKey={'value'}
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

export default LiveMemoryUsageCard
