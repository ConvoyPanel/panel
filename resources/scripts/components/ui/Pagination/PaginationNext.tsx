import { cn } from '@/utils'
import { ChevronRightIcon } from '@radix-ui/react-icons'
import { ComponentProps } from 'react'

import PaginationLink from '@/components/ui/Pagination/PaginationLink.tsx'


const PaginationNext = ({
    className,
    ...props
}: ComponentProps<typeof PaginationLink>) => (
    <PaginationLink
        aria-label='Go to next page'
        size='default'
        className={cn('gap-1 pr-2.5', className)}
        {...props}
    >
        <span>Next</span>
        <ChevronRightIcon className='h-4 w-4' />
    </PaginationLink>
)
PaginationNext.displayName = 'PaginationNext'

export default PaginationNext
