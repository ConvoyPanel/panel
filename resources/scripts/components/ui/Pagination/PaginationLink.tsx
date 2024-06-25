import { ComponentProps } from 'react'

import { Button, ButtonProps } from '@/components/ui/Button'

export type PaginationLinkProps = {
    isActive?: boolean
} & Pick<ButtonProps, 'size'> &
    ComponentProps<'button'>

const PaginationLink = ({
    isActive,
    size = 'icon',
    ...props
}: PaginationLinkProps) => (
    <Button
        aria-current={isActive ? 'page' : undefined}
        variant={isActive ? 'outline' : 'ghost'}
        {...props}
    />
)
PaginationLink.displayName = 'PaginationLink'

export default PaginationLink
