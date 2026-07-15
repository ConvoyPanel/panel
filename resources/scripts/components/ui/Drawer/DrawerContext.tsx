import { cn } from '@/utils'
import { Drawer as DrawerPrimitive } from '@base-ui/react/drawer'

import { DrawerOverlay, DrawerPortal } from '@/components/ui/Drawer'

/**
 * Base UI positions the popup itself and exposes the live drag offset as
 * `--drawer-swipe-movement-y`, so the transform follows the finger with no
 * transition while swiping and animates only on settle. `data-swiping` and
 * `data-ending-style` are Base UI's attributes — Radix/vaul's `data-state` does
 * not exist here and would silently never match.
 */
const DrawerContent = ({
    className,
    children,
    ...props
}: DrawerPrimitive.Popup.Props) => (
    <DrawerPortal>
        <DrawerOverlay />
        <DrawerPrimitive.Viewport>
            <DrawerPrimitive.Popup
                data-slot={'drawer-content'}
                className={cn(
                    'fixed inset-x-0 bottom-0 z-50 mt-24 flex h-auto flex-col rounded-t-[10px] border bg-background',
                    'translate-y-[calc(var(--drawer-snap-point-offset,0px)+var(--drawer-swipe-movement-y,0px))]',
                    'transition-transform duration-300 data-ending-style:translate-y-full data-starting-style:translate-y-full',
                    // Follow the finger 1:1 mid-gesture.
                    'data-swiping:transition-none',
                    'motion-reduce:transition-none',
                    className
                )}
                {...props}
            >
                <DrawerPrimitive.SwipeArea>
                    <div className='mx-auto mt-4 h-2 w-[100px] shrink-0 rounded-full bg-muted' />
                </DrawerPrimitive.SwipeArea>
                {children}
            </DrawerPrimitive.Popup>
        </DrawerPrimitive.Viewport>
    </DrawerPortal>
)
DrawerContent.displayName = 'DrawerContent'

export default DrawerContent
