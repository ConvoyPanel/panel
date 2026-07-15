import { cn } from '@/utils'
import { HTMLAttributes } from 'react'

const CommandShortcut = ({
    className,
    ...props
}: HTMLAttributes<HTMLSpanElement>) => {
    return (
        <span
            data-slot={'command-shortcut'}
            className={cn(
                'text-muted-foreground ml-auto text-xs tracking-widest',
                className
            )}
            {...props}
        />
    )
}
CommandShortcut.displayName = 'CommandShortcut'

export default CommandShortcut
