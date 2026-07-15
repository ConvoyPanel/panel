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
            data-slot={'input-otp-slot'}
            data-active={isActive || undefined}
            className={cn(
                'border-input aria-invalid:border-destructive data-[active=true]:border-ring data-[active=true]:ring-ring/50 data-[active=true]:aria-invalid:border-destructive data-[active=true]:aria-invalid:ring-destructive/20 dark:bg-input/30 dark:data-[active=true]:aria-invalid:ring-destructive/40 relative flex size-8 items-center justify-center border-y border-r text-sm transition-all outline-none first:rounded-l-lg first:border-l last:rounded-r-lg data-[active=true]:z-10 data-[active=true]:ring-3',
                className
            )}
            {...props}
        >
            {char}
            {hasFakeCaret && (
                <div className='pointer-events-none absolute inset-0 flex items-center justify-center'>
                    <div className='animate-caret-blink bg-foreground h-4 w-px duration-1000' />
                </div>
            )}
        </div>
    )
})
InputOTPSlot.displayName = 'InputOTPSlot'

export default InputOTPSlot
