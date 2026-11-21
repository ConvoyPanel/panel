import { cn } from '@/utils'
import { Progress } from '@base-ui/react/progress'
import { forwardRef } from 'react'

type LinearProgressBarProps = Omit<Progress.Root.Props, 'value'> & {
    value?: number | null
    /** Override the fill color, e.g. a capacity tone like `bg-destructive`. */
    indicatorClassName?: string
}

const LinearProgressBar = forwardRef<HTMLDivElement, LinearProgressBarProps>(
    ({ className, indicatorClassName, value = null, ...props }, ref) => (
        <Progress.Root
            ref={ref}
            value={value}
            className={cn(
                'bg-primary/20 relative h-2 w-full overflow-hidden rounded-full',
                className
            )}
            {...props}
        >
            {/* Base UI sizes the indicator itself (an inline width of
                value%). The translateX(-(100 - value)%) shadcn used with
                Radix, whose indicator was always full width, shifted that
                already-sized bar left by the remainder, so every meter drew
                value² -- 50% as 25%. With no value Base UI sets no width, so
                data-indeterminate keeps the track empty as before. */}
            <Progress.Indicator
                className={cn(
                    'bg-primary h-full transition-all data-indeterminate:w-0',
                    indicatorClassName
                )}
            />
        </Progress.Root>
    )
)
LinearProgressBar.displayName = 'LinearProgressBar'

export default LinearProgressBar
