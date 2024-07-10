import { cn } from '@/utils'
import * as ToastPrimitives from '@radix-ui/react-toast'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

const ToastTitle = forwardRef<
    ElementRef<typeof ToastPrimitives.Title>,
    ComponentPropsWithoutRef<typeof ToastPrimitives.Title>
>(({ className, ...props }, ref) => (
    <ToastPrimitives.Title
        ref={ref}
        className={cn('text-sm font-semibold [&+div]:text-xs', className)}
        {...props}
    />
))
ToastTitle.displayName = ToastPrimitives.Title.displayName

export default ToastTitle
