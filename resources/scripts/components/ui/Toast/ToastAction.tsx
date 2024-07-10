import { cn } from '@/utils'
import * as ToastPrimitives from '@radix-ui/react-toast'
import {
    ComponentPropsWithoutRef,
    ElementRef,
    ReactElement,
    forwardRef,
} from 'react'

export type ToastActionElement = ReactElement<typeof ToastAction>

const ToastAction = forwardRef<
    ElementRef<typeof ToastPrimitives.Action>,
    ComponentPropsWithoutRef<typeof ToastPrimitives.Action>
>(({ className, ...props }, ref) => (
    <ToastPrimitives.Action
        ref={ref}
        className={cn(
            'inline-flex h-8 shrink-0 items-center justify-center rounded-md border bg-transparent px-3 text-sm font-medium transition-colors hover:bg-secondary focus:outline-none focus:ring-1 focus:ring-ring disabled:pointer-events-none disabled:opacity-50 group-[.destructive]:border-muted/40 group-[.destructive]:hover:border-destructive/30 group-[.destructive]:hover:bg-destructive group-[.destructive]:hover:text-destructive-foreground group-[.destructive]:focus:ring-destructive',
            className
        )}
        {...props}
    />
))
ToastAction.displayName = ToastPrimitives.Action.displayName

export default ToastAction
