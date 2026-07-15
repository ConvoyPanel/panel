import { cn } from '@/utils'
import { HTMLAttributes } from 'react'

const DrawerHeader = ({
    className,
    ...props
}: HTMLAttributes<HTMLDivElement>) => (
    <div
        data-slot={'drawer-header'}
        // Matches the nova DialogHeader stack; left-aligned at every width.
        className={cn('flex flex-col gap-2 p-4 text-left', className)}
        {...props}
    />
)
DrawerHeader.displayName = 'DrawerHeader'

export default DrawerHeader
