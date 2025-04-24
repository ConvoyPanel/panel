import { cn } from '@/utils'
import * as ProgressPrimitive from '@radix-ui/react-progress'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

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

interface SegmentedProgressBarProps
    extends ComponentPropsWithoutRef<typeof ProgressPrimitive.Root> {
    segments: Segment[]
}

const SegmentedProgressBar = forwardRef<
    ElementRef<typeof ProgressPrimitive.Root>,
    SegmentedProgressBarProps
>(({ className, segments, ...props }, ref) => {
    let accumulated = 0

    return (
        <ProgressPrimitive.Root
            ref={ref}
            className={cn(
                'relative h-2 w-full overflow-hidden rounded-full bg-primary/10',
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
                                <ProgressPrimitive.Indicator
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
        </ProgressPrimitive.Root>
    )
})

SegmentedProgressBar.displayName = 'SegmentedProgressBar'

export default SegmentedProgressBar
