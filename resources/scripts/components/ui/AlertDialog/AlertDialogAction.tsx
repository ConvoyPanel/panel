import { cn } from '@/utils'
import * as AlertDialogPrimitive from '@radix-ui/react-alert-dialog'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

import { buttonVariants } from '@/components/ui/Button'

/**
 * Renders as a button by default. With `asChild` the child *is* the button and
 * owns its own variant, so this must not paint: Radix merges the parent's
 * className last, so `buttonVariants()` here beats a `variant='destructive'`
 * on the child and every destructive confirmation comes out primary-blue.
 */
const AlertDialogAction = forwardRef<
    ElementRef<typeof AlertDialogPrimitive.Action>,
    ComponentPropsWithoutRef<typeof AlertDialogPrimitive.Action>
>(({ className, asChild, ...props }, ref) => (
    <AlertDialogPrimitive.Action
        ref={ref}
        asChild={asChild}
        className={asChild ? className : cn(buttonVariants(), className)}
        {...props}
    />
))
AlertDialogAction.displayName = AlertDialogPrimitive.Action.displayName

export default AlertDialogAction
