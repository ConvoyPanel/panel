import { cn } from '@/utils'
import { ChevronRightIcon } from '@radix-ui/react-icons'
import { ComponentProps } from 'react'

const BreadcrumbSeparator = ({
    children,
    className,
    ...props
}: ComponentProps<'li'>) => (
    <li
        role='presentation'
        aria-hidden='true'
        className={cn('[&>svg]:size-3.5', className)}
        {...props}
    >
        {children ?? <ChevronRightIcon />}
    </li>
)
BreadcrumbSeparator.displayName = 'BreadcrumbSeparator'

export default BreadcrumbSeparator
