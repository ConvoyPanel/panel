import { cn } from '@/utils'
import * as AlertDialogPrimitive from '@radix-ui/react-alert-dialog'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

import { buttonVariants } from '@/components/ui/Button'

const AlertDialogCancel = forwardRef<
    ElementRef<typeof AlertDialogPrimitive.Cancel>,
    ComponentPropsWithoutRef<typeof AlertDialogPrimitive.Cancel>
>(({ className, asChild, ...props }, ref) => (
    <AlertDialogPrimitive.Cancel
        ref={ref}
        asChild={asChild}
        // Same reasoning as AlertDialogAction: with `asChild` the child paints
        // itself, and anything set here would win the merge.
        className={
            asChild
                ? cn('mt-2 sm:mt-0', className)
                : cn(
                      buttonVariants({ variant: 'outline' }),
                      'mt-2 sm:mt-0',
                      className
                  )
        }
        {...props}
    />
))
AlertDialogCancel.displayName = AlertDialogPrimitive.Cancel.displayName

export default AlertDialogCancel
