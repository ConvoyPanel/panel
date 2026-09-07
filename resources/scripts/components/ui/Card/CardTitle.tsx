import { cn } from '@/utils'
import { HTMLAttributes, forwardRef } from 'react'

type CardTitleProps = HTMLAttributes<HTMLHeadingElement> & {
    as?: 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6'
}

const CardTitle = forwardRef<HTMLHeadingElement, CardTitleProps>(
    ({ as: Component = 'h2', className, ...props }, ref) => (
        <Component
            ref={ref}
            className={cn('text-base leading-snug font-medium', className)}
            {...props}
        />
    )
)
CardTitle.displayName = 'CardTitle'

export default CardTitle
