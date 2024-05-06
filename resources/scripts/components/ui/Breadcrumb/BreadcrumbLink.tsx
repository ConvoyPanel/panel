import { cn } from '@/utils'
import { Slot } from '@radix-ui/react-slot'
import { ComponentPropsWithoutRef, forwardRef } from 'react'

const BreadcrumbLink = forwardRef<
    HTMLAnchorElement,
    ComponentPropsWithoutRef<'a'> & {
        asChild?: boolean
    }
>(({ asChild, className, ...props }, ref) => {
    const Comp = asChild ? Slot : 'a'

    return (
        <Comp
            ref={ref}
            className={cn('transition-colors hover:text-foreground', className)}
            {...props}
        />
    )
})
BreadcrumbLink.displayName = 'BreadcrumbLink'

export default BreadcrumbLink
