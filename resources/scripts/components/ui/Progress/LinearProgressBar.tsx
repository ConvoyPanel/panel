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
            <Progress.Indicator
                className={cn(
                    'bg-primary h-full w-full flex-1 transition-all',
                    indicatorClassName
                )}
                style={{ transform: `translateX(-${100 - (value ?? 0)}%)` }}
            />
        </Progress.Root>
    )
)
LinearProgressBar.displayName = 'LinearProgressBar'

export default LinearProgressBar
