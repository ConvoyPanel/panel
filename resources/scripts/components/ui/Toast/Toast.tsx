import { cn } from '@/utils'
import * as ToastPrimitives from '@radix-ui/react-toast'
import type { VariantProps } from 'class-variance-authority'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

import toastVariants from '@/components/ui/Toast/Toast.variants.ts'


export type ToastProps = ComponentPropsWithoutRef<typeof ToastPrimitives.Root> &
    VariantProps<typeof toastVariants>

const Toast = forwardRef<ElementRef<typeof ToastPrimitives.Root>, ToastProps>(
    ({ className, variant, ...props }, ref) => {
        return (
            <ToastPrimitives.Root
                ref={ref}
                className={cn(toastVariants({ variant }), className)}
                {...props}
            />
        )
    }
)
Toast.displayName = ToastPrimitives.Root.displayName

export default Toast
