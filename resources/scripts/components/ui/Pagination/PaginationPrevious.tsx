import { cn } from '@/utils'
import { ChevronLeftIcon } from '@radix-ui/react-icons'
import { ComponentProps } from 'react'

import { PaginationLink } from '@/components/ui/Pagination/index.ts'


const PaginationPrevious = ({
    className,
    ...props
}: ComponentProps<typeof PaginationLink>) => (
    <PaginationLink
        aria-label='Go to previous page'
        size='default'
        className={cn('gap-1 pl-2.5', className)}
        {...props}
    >
        <ChevronLeftIcon className='h-4 w-4' />
        <span>Previous</span>
    </PaginationLink>
)
PaginationPrevious.displayName = 'PaginationPrevious'

export default PaginationPrevious
