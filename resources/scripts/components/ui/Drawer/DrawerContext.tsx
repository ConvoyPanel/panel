import { cn } from '@/utils'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'
import { Drawer as DrawerPrimitive } from 'vaul'

import { DrawerOverlay, DrawerPortal } from '@/components/ui/Drawer/index.ts'


const DrawerContent = forwardRef<
    ElementRef<typeof DrawerPrimitive.Content>,
    ComponentPropsWithoutRef<typeof DrawerPrimitive.Content>
>(({ className, children, ...props }, ref) => (
    <DrawerPortal>
        <DrawerOverlay />
        <DrawerPrimitive.Content
            ref={ref}
            className={cn(
                'fixed inset-x-0 bottom-0 z-50 mt-24 flex h-auto flex-col rounded-t-[10px] border bg-background',
                className
            )}
            {...props}
        >
            <div className='mx-auto mt-4 h-2 w-[100px] rounded-full bg-muted' />
            {children}
        </DrawerPrimitive.Content>
    </DrawerPortal>
))
DrawerContent.displayName = 'DrawerContent'

export default DrawerContent
