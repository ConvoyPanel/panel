import { cn } from '@/utils'
import { Drawer as DrawerPrimitive } from '@base-ui/react/drawer'

const DrawerOverlay = ({
    className,
    ...props
}: DrawerPrimitive.Backdrop.Props) => (
    <DrawerPrimitive.Backdrop
        data-slot={'drawer-overlay'}
        className={cn(
            // Scrim matches the nova dialog backdrop.
            'fixed inset-0 isolate z-50 bg-black/10 supports-backdrop-filter:backdrop-blur-xs',
            // Base UI drives the backdrop from the live swipe progress, so it
            // fades with the drag instead of only on open/close.
            'opacity-[calc(1-var(--drawer-swipe-progress,0))] transition-opacity duration-300',
            'data-ending-style:opacity-0 data-starting-style:opacity-0',
            className
        )}
        {...props}
    />
)
DrawerOverlay.displayName = 'DrawerOverlay'

export default DrawerOverlay
