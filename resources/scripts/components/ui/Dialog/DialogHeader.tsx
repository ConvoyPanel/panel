import { cn } from '@/utils'
import { HTMLAttributes } from 'react'

const DialogHeader = ({
    className,
    ...props
}: HTMLAttributes<HTMLDivElement>) => (
    <div
        data-slot={'dialog-header'}
        // Values from the create-page default (base + style "nova"): a plain
        // gap-2 stack. nova left-aligns at every width — the old
        // `text-center sm:text-left` was shadcn's pre-nova mobile treatment.
        className={cn('flex flex-col gap-2', className)}
        {...props}
    />
)
DialogHeader.displayName = 'DialogHeader'

export default DialogHeader
