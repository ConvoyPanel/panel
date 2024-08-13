import { cn } from '@/utils'
import * as React from 'react'
import * as RechartsPrimitive from 'recharts'

import { getPayloadConfigFromPayload } from '@/components/ui/Chart/helpers.ts'
import useChart from '@/components/ui/Chart/use-chart.ts'


const ChartLegendContent = React.forwardRef<
    HTMLDivElement,
    React.ComponentProps<'div'> &
        Pick<RechartsPrimitive.LegendProps, 'payload' | 'verticalAlign'> & {
            hideIcon?: boolean
            nameKey?: string
        }
>(
    (
        {
            className,
            hideIcon = false,
            payload,
            verticalAlign = 'bottom',
            nameKey,
        },
        ref
    ) => {
        const { config } = useChart()

        if (!payload?.length) {
            return null
        }

        return (
            <div
                ref={ref}
                className={cn(
                    'flex items-center justify-center gap-4',
                    verticalAlign === 'top' ? 'pb-3' : 'pt-3',
                    className
                )}
            >
                {payload.map(item => {
                    const key = `${nameKey || item.dataKey || 'value'}`
                    const itemConfig = getPayloadConfigFromPayload(
                        config,
                        item,
                        key
                    )

                    return (
                        <div
                            key={item.value}
                            className={cn(
                                'flex items-center gap-1.5 [&>svg]:h-3 [&>svg]:w-3 [&>svg]:text-muted-foreground'
                            )}
                        >
                            {itemConfig?.icon && !hideIcon ? (
                                <itemConfig.icon />
                            ) : (
                                <div
                                    className='h-2 w-2 shrink-0 rounded-[2px]'
                                    style={{
                                        backgroundColor: item.color,
                                    }}
                                />
                            )}
                            {itemConfig?.label}
                        </div>
                    )
                })}
            </div>
        )
    }
)
ChartLegendContent.displayName = 'ChartLegend'

export default ChartLegendContent
