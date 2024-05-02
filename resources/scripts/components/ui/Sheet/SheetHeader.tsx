import { cn } from '@/utils'
import { HTMLAttributes } from 'react'

const SheetHeader = ({
    className,
    ...props
}: HTMLAttributes<HTMLDivElement>) => (
    <div
        className={cn(
            'flex flex-col space-y-2 text-center sm:text-left',
            className
        )}
        {...props}
    />
)
SheetHeader.displayName = 'SheetHeader'

export default SheetHeader
