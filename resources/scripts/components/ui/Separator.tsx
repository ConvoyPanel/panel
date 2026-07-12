import { cn } from '@/utils'
import { Separator as BaseSeparator } from '@base-ui/react/separator'
import * as React from 'react'

interface SeparatorProps extends BaseSeparator.Props {
    decorative?: boolean
}

const Separator = React.forwardRef<HTMLDivElement, SeparatorProps>(
    (
        { className, orientation = 'horizontal', decorative = true, ...props },
        ref
    ) => (
        <BaseSeparator
            ref={ref}
            orientation={orientation}
            role={decorative ? 'none' : undefined}
            className={cn(
                'bg-border shrink-0',
                orientation === 'horizontal'
                    ? 'h-[1px] w-full'
                    : 'h-full w-[1px]',
                className
            )}
            {...props}
        />
    )
)
Separator.displayName = 'Separator'

export { Separator }
