import { cn } from '@/utils'
import * as ProgressPrimitive from '@radix-ui/react-progress'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

type LinearProgressBarProps = ComponentPropsWithoutRef<
    typeof ProgressPrimitive.Root
> & {
    /** Override the fill color, e.g. a capacity tone like `bg-destructive`. */
    indicatorClassName?: string
}

const LinearProgressBar = forwardRef<
    ElementRef<typeof ProgressPrimitive.Root>,
    LinearProgressBarProps
>(({ className, indicatorClassName, value, ...props }, ref) => (
    <ProgressPrimitive.Root
        ref={ref}
        className={cn(
            'bg-primary/20 relative h-2 w-full overflow-hidden rounded-full',
            className
        )}
        {...props}
    >
        <ProgressPrimitive.Indicator
            className={cn(
                'bg-primary h-full w-full flex-1 transition-all',
                indicatorClassName
            )}
            style={{ transform: `translateX(-${100 - (value || 0)}%)` }}
        />
    </ProgressPrimitive.Root>
))
LinearProgressBar.displayName = ProgressPrimitive.Root.displayName

export default LinearProgressBar
