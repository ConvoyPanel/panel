import { cn } from '@/utils'
import { HTMLAttributes, forwardRef } from 'react'

const CardFooter = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn('flex items-center border-t bg-muted/50 p-4', className)}
            {...props}
        />
    )
)
CardFooter.displayName = 'CardFooter'

export default CardFooter
