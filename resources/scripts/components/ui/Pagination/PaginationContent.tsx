import { cn } from '@/utils'
import { ComponentProps, forwardRef } from 'react'

const PaginationContent = forwardRef<HTMLUListElement, ComponentProps<'ul'>>(
    ({ className, ...props }, ref) => (
        <ul
            ref={ref}
            className={cn('flex flex-row items-center gap-1', className)}
            {...props}
        />
    )
)
PaginationContent.displayName = 'PaginationContent'

export default PaginationContent
