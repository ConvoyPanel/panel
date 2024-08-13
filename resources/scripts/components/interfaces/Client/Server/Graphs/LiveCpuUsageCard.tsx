import { useEffect, useState } from 'react'
import { Area, AreaChart, CartesianGrid, YAxis } from 'recharts'

import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { ChartContainer } from '@/components/ui/Chart'


const LiveCpuUsageCard = () => {
    const { data: state } = useServerStateSWR()
    const [data, setData] = useState(
        Array.from({ length: 10 }, () => ({ value: 0 }))
    )

    useEffect(() => {
        if (!state) return

        setData(prev => {
            const next = [...prev]
            next.shift()
            next.push({ value: Math.floor(state.cpuUsed * 100) })
            return next
        })
    }, [state])

    return (
        <Card>
            <CardHeader>
                <CardTitle>Live CPU Usage</CardTitle>
            </CardHeader>
            <CardContent>
                <ChartContainer config={{}} className='min-h-[200px] w-full'>
                    <AreaChart accessibilityLayer data={data}>
                        <YAxis ticks={[0, 50, 100]} width={25} />
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

export default LiveCpuUsageCard
