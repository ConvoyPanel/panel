import { cn } from '@/utils'
import { HTMLAttributes, forwardRef } from 'react'

/**
 * CardAction — the top-right slot in a CardHeader (e.g. a "New" button or a
 * menu), from the latest shadcn Card. Relies on CardHeader switching to a
 * `grid-cols-[1fr_auto]` layout when a `[data-slot=card-action]` child exists.
 */
const CardAction = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            data-slot='card-action'
            className={cn(
                'col-start-2 row-span-2 row-start-1 self-start justify-self-end',
                className
            )}
            {...props}
        />
    )
)
CardAction.displayName = 'CardAction'

export default CardAction
