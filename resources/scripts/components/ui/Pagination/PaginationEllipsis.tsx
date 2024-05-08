import { cn } from '@/utils'
import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { ComponentProps } from 'react'

const PaginationEllipsis = ({
    className,
    ...props
}: ComponentProps<'span'>) => (
    <span
        aria-hidden
        className={cn('flex h-9 w-9 items-center justify-center', className)}
        {...props}
    >
        <DotsHorizontalIcon className='h-4 w-4' />
        <span className='sr-only'>More pages</span>
    </span>
)
PaginationEllipsis.displayName = 'PaginationEllipsis'

export default PaginationEllipsis
