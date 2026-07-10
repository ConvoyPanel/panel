import { cn } from '@/utils'
import { HTMLAttributes, forwardRef } from 'react'

const CardContent = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div ref={ref} className={cn('p-4 pt-0', className)} {...props} />
    )
)
CardContent.displayName = 'CardContent'

export default CardContent
