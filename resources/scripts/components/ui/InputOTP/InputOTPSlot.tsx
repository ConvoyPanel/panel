import { cn } from '@/utils'
import { OTPInputContext } from 'input-otp'
import {
    ComponentPropsWithoutRef,
    ElementRef,
    forwardRef,
    useContext,
} from 'react'

const InputOTPSlot = forwardRef<
    ElementRef<'div'>,
    ComponentPropsWithoutRef<'div'> & { index: number }
>(({ index, className, ...props }, ref) => {
    const inputOTPContext = useContext(OTPInputContext)
    const { char, hasFakeCaret, isActive } = inputOTPContext.slots[index]

    return (
        <div
            ref={ref}
            className={cn(
                'relative flex h-9 w-9 items-center justify-center border-y border-r border-input text-sm shadow-xs transition-all first:rounded-l-md first:border-l last:rounded-r-md',
                isActive && 'z-10 ring-1 ring-ring',
                className
            )}
            {...props}
        >
            {char}
            {hasFakeCaret && (
                <div className='pointer-events-none absolute inset-0 flex items-center justify-center'>
                    <div className='animate-caret-blink h-4 w-px bg-foreground duration-1000' />
                </div>
            )}
        </div>
    )
})
InputOTPSlot.displayName = 'InputOTPSlot'

export default InputOTPSlot
