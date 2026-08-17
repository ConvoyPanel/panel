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
                    'group/drawer',
                    // Surface matches the nova dialog popup: rounded-xl on
                    // bg-popover with a flat ring instead of a border.
                    // `outline-none` for the same reason DialogContent carries
                    // it: Base UI moves focus to the popup when it opens, and
                    // without this the browser draws its own focus ring around
                    // the entire sheet -- a blue rectangle framing the drawer,
                    // which reads as a rendering fault rather than focus.
                    'fixed inset-x-0 bottom-0 z-50 mt-24 flex h-auto flex-col rounded-t-xl bg-popover text-sm text-popover-foreground ring-1 ring-foreground/10 outline-none',
                    // Depth cue for nested drawers, the drawer-side counterpart
                    // of DialogContent's offset/scale/tint. Base UI hides a
                    // nested drawer's backdrop exactly like a nested dialog's, so
                    // without this a stacked drawer was two identical sheets with
                    // two visible grabbers and nothing to tell them apart.
                    //
                    // --nested-drawers (NOT --nested-dialogs; the drawer popup
                    // publishes its own counter) is the open-descendant count.
                    // Lift by a constant peek and scale from the bottom edge, so
                    // the parent stays anchored and only its top peeks out.
                    // The --shrink term is not optional padding on the maths: with
                    // a bottom origin, scaling by s drags the top edge DOWN by
                    // (1-s)*height, which silently ate the peek and left the two
                    // sheets' top edges a pixel apart. Translating up by
                    // shrink*height cancels the scale first, then --peek is what
                    // actually shows. Height comes from the frontmost drawer, so
                    // the peek stays constant regardless of either sheet's size.
                    '[--peek:1rem] [--stack-step:0.05]',
                    '[--scale:calc(1-var(--stack-step)*var(--nested-drawers,0))]',
                    '[--shrink:calc(1-var(--scale))]',
                    '[--stack-height:var(--drawer-frontmost-height,var(--drawer-height,0px))]',
                    'origin-bottom scale-[var(--scale)]',
                    'translate-y-[calc(var(--drawer-snap-point-offset,0px)+var(--drawer-swipe-movement-y,0px)-var(--peek)*var(--nested-drawers,0)-var(--shrink)*var(--stack-height))]',
                    'after:pointer-events-none after:absolute after:inset-0 after:rounded-[inherit] after:bg-foreground/5 after:opacity-0 after:transition-opacity data-nested-drawer-open:after:opacity-100',
                    'transition-[transform,translate,scale,opacity] duration-300 data-ending-style:translate-y-full data-starting-style:translate-y-full',
                    // Follow the finger 1:1 mid-gesture.
                    'data-swiping:transition-none',
                    'motion-reduce:transition-none',
                    className
                )}
                {...props}
            >
                {/* The grabber belongs to whichever drawer is frontmost: leaving
                    it on a stacked-back parent rendered two of them. */}
                <DrawerPrimitive.SwipeArea>
                    <div className='mx-auto mt-4 h-2 w-[100px] shrink-0 rounded-full bg-muted transition-opacity duration-200 group-data-nested-drawer-open/drawer:opacity-0' />
                </DrawerPrimitive.SwipeArea>
                {children}
            </DrawerPrimitive.Popup>
        </DrawerPrimitive.Viewport>
    </DrawerPortal>
)
DrawerContent.displayName = 'DrawerContent'

export default DrawerContent
