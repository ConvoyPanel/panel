import { cn } from '@/utils'
import { HTMLAttributes, forwardRef } from 'react'

const CardFooter = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn(
                // `last:` so the tinted footer follows the card's rounded
                // corner when it closes the card — including through a wrapper
                // like a <form>, since :last-child is relative to its parent.
                'bg-muted/50 flex items-center border-t p-4 last:rounded-b-xl',
                className
            )}
            {...props}
        />
    )
)
CardFooter.displayName = 'CardFooter'

export default CardFooter
