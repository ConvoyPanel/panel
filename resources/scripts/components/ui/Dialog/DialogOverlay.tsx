import { cn } from '@/utils'
import { Dialog as DialogPrimitive } from '@base-ui/react/dialog'

const DialogOverlay = ({
    className,
    ...props
}: DialogPrimitive.Backdrop.Props) => (
    <DialogPrimitive.Backdrop
        data-slot={'dialog-overlay'}
        className={cn(
            // Values from the create-page default (base + style "nova"), source
            // apps/v4/styles/base-nova/ui/dialog.tsx. nova scrims with a light
            // wash plus a blur instead of shadcn's older opaque `bg-black/80`,
            // which is what made these read as heavy modal takeovers.
            'fixed inset-0 isolate z-50 bg-black/10 transition-opacity duration-100 supports-backdrop-filter:backdrop-blur-xs',
            // Base UI attributes: data-starting-style/data-ending-style, NOT
            // Radix's data-[state=open]/[state=closed].
            'data-ending-style:opacity-0 data-starting-style:opacity-0',
            'motion-reduce:transition-none',
            className
        )}
        {...props}
    />
)
DialogOverlay.displayName = 'DialogOverlay'

export default DialogOverlay
