import { cn } from '@/utils'
import { Dialog as DialogPrimitive } from '@base-ui/react/dialog'

const DialogOverlay = ({
    className,
    ...props
}: DialogPrimitive.Backdrop.Props) => (
    <DialogPrimitive.Backdrop
        data-slot={'dialog-overlay'}
        className={cn(
            'fixed inset-0 z-50 bg-black/80 transition-opacity duration-200',
            // Base UI attributes: data-starting-style/data-ending-style, NOT
            // Radix's data-[state=open]/[state=closed].
            'data-ending-style:opacity-0 data-starting-style:opacity-0',
            className
        )}
        {...props}
    />
)
DialogOverlay.displayName = 'DialogOverlay'

export default DialogOverlay
