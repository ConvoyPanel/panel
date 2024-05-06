import { cn } from '@/utils'
import { ComponentPropsWithoutRef, forwardRef } from 'react'

const BreadcrumbItem = forwardRef<
    HTMLLIElement,
    ComponentPropsWithoutRef<'li'>
>(({ className, ...props }, ref) => (
    <li
        ref={ref}
        className={cn('inline-flex items-center gap-1.5', className)}
        {...props}
    />
))
BreadcrumbItem.displayName = 'BreadcrumbItem'

export default BreadcrumbItem
