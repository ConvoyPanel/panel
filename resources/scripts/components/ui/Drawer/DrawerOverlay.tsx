import { cn } from '@/utils'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'
import { Drawer as DrawerPrimitive } from 'vaul'

const DrawerOverlay = forwardRef<
    ElementRef<typeof DrawerPrimitive.Overlay>,
    ComponentPropsWithoutRef<typeof DrawerPrimitive.Overlay>
>(({ className, ...props }, ref) => (
    <DrawerPrimitive.Overlay
        ref={ref}
        className={cn('fixed inset-0 z-50 bg-black/80', className)}
        {...props}
    />
))
DrawerOverlay.displayName = DrawerPrimitive.Overlay.displayName

export default DrawerOverlay
