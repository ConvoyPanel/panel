import { cn } from '@/utils'
import type { ComponentProps } from 'react'

import { Separator } from '@/components/ui/Separator'

/**
 * The seam between two filled group members.
 *
 * nova writes the orientation rules as bare `data-horizontal:` /
 * `data-vertical:`; Base UI emits `data-orientation="horizontal"` instead, so
 * ported verbatim they would compile and silently never match. Same trap as the
 * toggle-group port.
 */
const ButtonGroupSeparator = ({
    className,
    orientation = 'vertical',
    ...props
}: ComponentProps<typeof Separator>) => (
    <Separator
        data-slot={'button-group-separator'}
        orientation={orientation}
        className={cn(
            'bg-input relative self-stretch data-[orientation=horizontal]:mx-px data-[orientation=horizontal]:w-auto data-[orientation=vertical]:my-px data-[orientation=vertical]:h-auto',
            className
        )}
        {...props}
    />
)
ButtonGroupSeparator.displayName = 'ButtonGroupSeparator'

export default ButtonGroupSeparator
