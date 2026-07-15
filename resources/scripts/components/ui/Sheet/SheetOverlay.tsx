import { cn } from '@/utils'
import { Dialog as SheetPrimitive } from '@base-ui/react/dialog'

const SheetOverlay = ({
    className,
    ...props
}: SheetPrimitive.Backdrop.Props) => (
    <SheetPrimitive.Backdrop
        data-slot={'sheet-overlay'}
        className={cn(
            'fixed inset-0 z-50 bg-black/80 transition-opacity duration-300',
            'data-ending-style:opacity-0 data-starting-style:opacity-0',
            className
        )}
        {...props}
    />
)
SheetOverlay.displayName = 'SheetOverlay'

export default SheetOverlay
