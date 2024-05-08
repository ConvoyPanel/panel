import { cn } from '@/utils'
import { Link } from '@tanstack/react-router'
import { ComponentProps } from 'react'

import { ButtonProps, buttonVariants } from '@/components/ui/Button'


export type PaginationLinkProps = {
    isActive?: boolean
} & Pick<ButtonProps, 'size'> &
    Pick<ComponentProps<'a'>, 'className'>

const PaginationLink = ({
    className,
    isActive,
    size = 'icon',
    ...props
}: PaginationLinkProps) => (
    <Link
        aria-current={isActive ? 'page' : undefined}
        className={cn(
            buttonVariants({
                variant: isActive ? 'outline' : 'ghost',
                size,
            }),
            className
        )}
        {...props}
    />
)
PaginationLink.displayName = 'PaginationLink'

export default PaginationLink
