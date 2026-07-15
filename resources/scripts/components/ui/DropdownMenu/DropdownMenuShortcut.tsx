import { cn } from '@/utils'
import { HTMLAttributes } from 'react'

const DropdownMenuShortcut = ({
    className,
    ...props
}: HTMLAttributes<HTMLSpanElement>) => {
    return (
        <span
            data-slot={'dropdown-menu-shortcut'}
            className={cn(
                'text-muted-foreground group-focus/dropdown-menu-item:text-accent-foreground ml-auto text-xs tracking-widest',
                className
            )}
            {...props}
        />
    )
}
DropdownMenuShortcut.displayName = 'DropdownMenuShortcut'

export default DropdownMenuShortcut
