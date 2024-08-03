import { cn } from '@/utils'
import { HTMLAttributes } from 'react'

const DrawerHeader = ({
    className,
    ...props
}: HTMLAttributes<HTMLDivElement>) => (
    <div
        className={cn('grid gap-1.5 p-4 text-center sm:text-left', className)}
        {...props}
    />
)
DrawerHeader.displayName = 'DrawerHeader'

export default DrawerHeader
