import { cn } from '@/utils'
import { ComponentPropsWithoutRef, forwardRef } from 'react'

const BreadcrumbPage = forwardRef<
    HTMLSpanElement,
    ComponentPropsWithoutRef<'span'>
>(({ className, ...props }, ref) => (
    <span
        ref={ref}
        role='link'
        aria-disabled='true'
        aria-current='page'
        className={cn('font-normal text-foreground', className)}
        {...props}
    />
))
BreadcrumbPage.displayName = 'BreadcrumbPage'

export default BreadcrumbPage
