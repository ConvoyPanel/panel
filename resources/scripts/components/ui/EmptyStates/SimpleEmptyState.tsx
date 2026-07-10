import { ComponentProps, ReactNode } from 'react'

import { TablerIcon } from '@/lib/tabler.ts'

import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/Empty'

interface Props extends ComponentProps<'div'> {
    icon: TablerIcon
    title: string
    description?: string
    action?: ReactNode
}

/**
 * Thin abstraction over the shadcn Empty primitive so callers keep passing
 * `{ icon, title, description, action }` and inherit the latest empty-state
 * styling without recomposing every usage.
 */
const SimpleEmptyState = ({
    icon: Icon,
    title,
    description,
    action,
    ...props
}: Props) => {
    return (
        <Empty {...props}>
            <EmptyHeader>
                <EmptyMedia variant={'icon'}>
                    <Icon />
                </EmptyMedia>
                <EmptyTitle>{title}</EmptyTitle>
                {description && (
                    <EmptyDescription>{description}</EmptyDescription>
                )}
            </EmptyHeader>
            {action && <EmptyContent>{action}</EmptyContent>}
        </Empty>
    )
}

export default SimpleEmptyState
