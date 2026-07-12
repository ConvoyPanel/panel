import { cn } from '@/utils'
import { Progress } from '@base-ui/react/progress'
import { forwardRef } from 'react'

import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/Tooltip'

export interface Segment {
    value: number
    color: string
    label: string
}

interface SegmentedProgressBarProps extends Omit<Progress.Root.Props, 'value'> {
    segments: Segment[]
    value?: number | null
}

const SegmentedProgressBar = forwardRef<
    HTMLDivElement,
    SegmentedProgressBarProps
>(({ className, segments, value, ...props }, ref) => {
    let accumulated = 0
    const total = Math.min(
        segments.reduce((sum, segment) => sum + segment.value, 0),
        100
    )

    return (
        <Progress.Root
            ref={ref}
            value={value ?? total}
            className={cn(
                'bg-primary/10 relative h-2 w-full overflow-hidden rounded-full',
                className
            )}
            {...props}
        >
            <TooltipProvider>
                {segments.map((segment, index) => {
                    const start = accumulated
                    accumulated += segment.value
                    const width = segment.value
                    const startPosition = start

                    return (
                        <Tooltip key={index}>
                            <TooltipTrigger asChild>
                                <Progress.Indicator
                                    className='absolute h-full transition-all'
                                    style={{
                                        left: `${startPosition}%`,
                                        width: `${width}%`,
                                        backgroundColor: segment.color,
                                    }}
                                />
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>
                                    {segment.label}: {segment.value.toFixed(1)}%
                                </p>
                            </TooltipContent>
                        </Tooltip>
                    )
                })}
            </TooltipProvider>
        </Progress.Root>
    )
})

SegmentedProgressBar.displayName = 'SegmentedProgressBar'

export default SegmentedProgressBar
