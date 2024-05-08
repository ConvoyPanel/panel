import { cn } from '@/utils'
import { ComponentProps, forwardRef } from 'react'

const PaginationItem = forwardRef<HTMLLIElement, ComponentProps<'li'>>(
    ({ className, ...props }, ref) => (
        <li ref={ref} className={cn('', className)} {...props} />
    )
)
PaginationItem.displayName = 'PaginationItem'

export default PaginationItem
