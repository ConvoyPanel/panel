import { cn } from '@/utils'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

const InputOTPGroup = forwardRef<
    ElementRef<'div'>,
    ComponentPropsWithoutRef<'div'>
>(({ className, ...props }, ref) => (
    <div
        ref={ref}
        data-slot={'input-otp-group'}
        className={cn(
            'has-aria-invalid:border-destructive has-aria-invalid:ring-destructive/20 dark:has-aria-invalid:ring-destructive/40 flex items-center rounded-lg has-aria-invalid:ring-3',
            className
        )}
        {...props}
    />
))
InputOTPGroup.displayName = 'InputOTPGroup'

export default InputOTPGroup
