import { cn } from '@/utils'
import { HTMLAttributes } from 'react'

const DrawerFooter = ({
    className,
    ...props
}: HTMLAttributes<HTMLDivElement>) => (
    <div
        className={cn('mt-auto flex flex-col gap-2 p-4', className)}
        {...props}
    />
)
DrawerFooter.displayName = 'DrawerFooter'

export default DrawerFooter
