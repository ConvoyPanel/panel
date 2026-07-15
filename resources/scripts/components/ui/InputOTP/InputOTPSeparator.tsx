import { MinusIcon } from '@radix-ui/react-icons'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

const InputOTPSeparator = forwardRef<
    ElementRef<'div'>,
    ComponentPropsWithoutRef<'div'>
>(({ ...props }, ref) => (
    <div
        ref={ref}
        data-slot={'input-otp-separator'}
        className={'flex items-center [&_svg:not([class*=size-])]:size-4'}
        role='separator'
        {...props}
    >
        <MinusIcon />
    </div>
))
InputOTPSeparator.displayName = 'InputOTPSeparator'

export default InputOTPSeparator
