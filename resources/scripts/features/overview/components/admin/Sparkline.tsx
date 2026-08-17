import { cn } from '@/utils'
import { Area, AreaChart } from 'recharts'

import ChartContainer from '@/components/ui/Chart/ChartContainer'

/** Compact shadcn/Recharts trend line for the KPI tiles. */
const Sparkline = ({
    series,
    className,
}: {
    series: number[]
    className?: string
}) => {
    if (series.length < 2) {
        return null
    }

    const data = series.map((value, index) => ({ index, value }))

    return (
        <ChartContainer
            config={{ value: { color: 'var(--primary)' } }}
            className={cn('pointer-events-none block', className)}
        >
            <AreaChart
                data={data}
                margin={{ top: 2, right: 0, bottom: 0, left: 0 }}
            >
                <Area
                    activeDot={false}
                    isAnimationActive={false}
                    type='monotone'
                    dataKey='value'
                    stroke='var(--color-value)'
                    strokeWidth={1.5}
                    fill='var(--color-value)'
                    fillOpacity={0.1}
                    dot={false}
                />
            </AreaChart>
        </ChartContainer>
    )
}

export default Sparkline
