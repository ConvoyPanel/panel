import { cn } from '@/utils'
import { HTMLAttributes } from 'react'

const DialogHeader = ({
    className,
    ...props
}: HTMLAttributes<HTMLDivElement>) => (
    <div
        className={cn(
            'flex flex-col space-y-1.5 text-center sm:text-left',
            className
        )}
        {...props}
    />
)
DialogHeader.displayName = 'DialogHeader'

export default DialogHeader
