import { cn } from '@/utils'
import { HTMLAttributes, forwardRef } from 'react'

const Card = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn(
                // Values from the shadcn create-page default (base + style
                // "nova"): a flat subtle ring instead of border+shadow, and a
                // text-sm base. Not new-york-v4, which deviates.
                'rounded-xl bg-card text-sm text-card-foreground ring-1 ring-foreground/10',
                className
            )}
            {...props}
        />
    )
)
Card.displayName = 'Card'

export default Card
