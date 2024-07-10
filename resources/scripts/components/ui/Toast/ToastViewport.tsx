import { cn } from '@/utils'
import * as ToastPrimitives from '@radix-ui/react-toast'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

const ToastViewport = forwardRef<
    ElementRef<typeof ToastPrimitives.Viewport>,
    ComponentPropsWithoutRef<typeof ToastPrimitives.Viewport>
>(({ className, ...props }, ref) => (
    <ToastPrimitives.Viewport
        ref={ref}
        className={cn(
            'fixed top-0 z-[100] flex max-h-screen w-full flex-col-reverse p-4 sm:bottom-0 sm:right-0 sm:top-auto sm:flex-col md:max-w-[420px]',
            className
        )}
        {...props}
    />
))
ToastViewport.displayName = ToastPrimitives.Viewport.displayName

export default ToastViewport
