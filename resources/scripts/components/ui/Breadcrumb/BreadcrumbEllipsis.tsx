import { cn } from '@/utils'
import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { ComponentProps } from 'react'

const BreadcrumbEllipsis = ({
    className,
    ...props
}: ComponentProps<'span'>) => (
    <span
        role='presentation'
        aria-hidden='true'
        className={cn('flex h-9 w-9 items-center justify-center', className)}
        {...props}
    >
        <DotsHorizontalIcon className='h-4 w-4' />
        <span className='sr-only'>More</span>
    </span>
)
BreadcrumbEllipsis.displayName = 'BreadcrumbElipssis'

export default BreadcrumbEllipsis
