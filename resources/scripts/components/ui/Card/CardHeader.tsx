import { cn } from '@/utils'
import { HTMLAttributes, forwardRef } from 'react'

const CardHeader = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn(
                'flex flex-col space-y-1.5 p-6',
                // When a CardAction is present, switch to a two-column grid so
                // the action sits top-right. Cards without one are unchanged.
                'has-data-[slot=card-action]:grid has-data-[slot=card-action]:grid-cols-[1fr_auto] has-data-[slot=card-action]:items-start has-data-[slot=card-action]:gap-x-4 has-data-[slot=card-action]:gap-y-1.5 has-data-[slot=card-action]:space-y-0',
                className
            )}
            {...props}
        />
    )
)
CardHeader.displayName = 'CardHeader'

export default CardHeader
