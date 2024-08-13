import { cn } from '@/utils'
import { HTMLAttributes } from 'react'

const AlertDialogFooter = ({
    className,
    ...props
}: HTMLAttributes<HTMLDivElement>) => (
    <div
        className={cn(
            'flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2',
            className
        )}
        {...props}
    />
)
AlertDialogFooter.displayName = 'AlertDialogFooter'

export default AlertDialogFooter
