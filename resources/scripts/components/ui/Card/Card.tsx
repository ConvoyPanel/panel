import { cn } from '@/utils'
import { HTMLAttributes, forwardRef } from 'react'

const Card = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn(
                'rounded-xl border bg-card text-card-foreground shadow',
                className
            )}
            {...props}
        />
    )
)
Card.displayName = 'Card'

export default Card
