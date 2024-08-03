import { cn } from '@/utils'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'
import { Drawer as DrawerPrimitive } from 'vaul'

const DrawerDescription = forwardRef<
    ElementRef<typeof DrawerPrimitive.Description>,
    ComponentPropsWithoutRef<typeof DrawerPrimitive.Description>
>(({ className, ...props }, ref) => (
    <DrawerPrimitive.Description
        ref={ref}
        className={cn('text-sm text-muted-foreground', className)}
        {...props}
    />
))
DrawerDescription.displayName = DrawerPrimitive.Description.displayName

export default DrawerDescription
