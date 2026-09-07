import { cn } from '@/utils'
import { HTMLAttributes, forwardRef } from 'react'

/*
 * One named step above the default, for a card that *is* the page — the auth
 * screens, where nothing else on screen competes with it. Named so those
 * screens stop reaching past the scale by hand: the login page was setting
 * `text-3xl`, which put 30px of "Sign in" above 32px fields and 14px body copy.
 */
const SIZES = {
    default: 'text-base leading-snug font-medium',
    display: 'text-xl leading-tight font-medium tracking-[-0.012em]',
} as const

type CardTitleProps = HTMLAttributes<HTMLHeadingElement> & {
    as?: 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6'
    size?: keyof typeof SIZES
}

const CardTitle = forwardRef<HTMLHeadingElement, CardTitleProps>(
    ({ as: Component = 'h2', size = 'default', className, ...props }, ref) => (
        <Component
            ref={ref}
            className={cn(SIZES[size], className)}
            {...props}
        />
    )
)
CardTitle.displayName = 'CardTitle'

export default CardTitle
