import { cn } from '@/utils'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'
import { Drawer as DrawerPrimitive } from 'vaul'

const DrawerTitle = forwardRef<
    ElementRef<typeof DrawerPrimitive.Title>,
    ComponentPropsWithoutRef<typeof DrawerPrimitive.Title>
>(({ className, ...props }, ref) => (
    <DrawerPrimitive.Title
        ref={ref}
        className={cn(
            'text-lg font-semibold leading-none tracking-tight',
            className
        )}
        {...props}
    />
))
DrawerTitle.displayName = DrawerPrimitive.Title.displayName

export default DrawerTitle
