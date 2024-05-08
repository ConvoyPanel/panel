import { cn } from '@/utils'
import { ComponentProps } from 'react'

const Pagination = ({ className, ...props }: ComponentProps<'nav'>) => (
    <nav
        role='navigation'
        aria-label='pagination'
        className={cn('mx-auto flex w-full justify-center', className)}
        {...props}
    />
)
Pagination.displayName = 'Pagination'

export default Pagination
