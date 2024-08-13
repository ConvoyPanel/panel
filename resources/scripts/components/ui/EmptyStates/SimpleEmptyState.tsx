import { cn } from '@/utils'
import { ComponentProps, ReactNode } from 'react'

import { TablerIcon } from '@/lib/tabler.ts'

interface Props extends ComponentProps<'div'> {
    icon: TablerIcon
    title: string
    description?: string
    action?: ReactNode
}

const SimpleEmptyState = ({
    icon: Icon,
    title,
    description,
    action,
    className,
    ...props
}: Props) => {
    return (
        <div className={cn('text-center', className)} {...props}>
            <Icon
                className={'mx-auto h-12 w-12 text-muted-foreground'}
                stroke={1}
            />
            <h3 className={'mt-3 text-sm font-semibold'}>{title}</h3>
            {description && (
                <p
                    className={
                        'mx-auto mt-0.5 max-w-md text-sm text-muted-foreground'
                    }
                >
                    {description}
                </p>
            )}
            {action && <div className={'mt-6'}>{action}</div>}
        </div>
    )
}

export default SimpleEmptyState
