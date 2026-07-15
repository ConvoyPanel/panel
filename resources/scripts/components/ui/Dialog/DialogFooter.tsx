import { cn } from '@/utils'
import { HTMLAttributes } from 'react'

const DialogFooter = ({
    className,
    ...props
}: HTMLAttributes<HTMLDivElement>) => (
    <div
        data-slot={'dialog-footer'}
        // Values from the create-page default (base + style "nova"): the actions
        // sit in a muted bar bonded to the bottom of the popup. The negative
        // margins cancel the popup's `p-4` so the bar goes edge-to-edge, so this
        // only lines up inside a nova DialogContent.
        className={cn(
            '-mx-4 -mb-4 flex flex-col-reverse gap-2 rounded-b-xl border-t bg-muted/50 p-4 sm:flex-row sm:justify-end',
            className
        )}
        {...props}
    />
)
DialogFooter.displayName = 'DialogFooter'

export default DialogFooter
