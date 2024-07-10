import { cn } from '@/utils'
import { Cross2Icon } from '@radix-ui/react-icons'
import * as ToastPrimitives from '@radix-ui/react-toast'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'


const ToastClose = forwardRef<
    ElementRef<typeof ToastPrimitives.Close>,
    ComponentPropsWithoutRef<typeof ToastPrimitives.Close>
>(({ className, ...props }, ref) => (
    <ToastPrimitives.Close
        ref={ref}
        className={cn(
            'absolute right-1 top-1 rounded-md p-1 text-foreground/50 opacity-0 transition-opacity hover:text-foreground focus:opacity-100 focus:outline-none focus:ring-1 group-hover:opacity-100 group-[.destructive]:text-red-300 group-[.destructive]:hover:text-red-50 group-[.destructive]:focus:ring-red-400 group-[.destructive]:focus:ring-offset-red-600',
            className
        )}
        toast-close=''
        {...props}
    >
        <Cross2Icon className='h-4 w-4' />
    </ToastPrimitives.Close>
))
ToastClose.displayName = ToastPrimitives.Close.displayName

export default ToastClose
