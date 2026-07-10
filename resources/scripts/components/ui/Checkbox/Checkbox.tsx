import { cn } from '@/utils'
import * as CheckboxPrimitive from '@radix-ui/react-checkbox'
import { CheckIcon } from '@radix-ui/react-icons'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

export interface CheckboxProps
    extends ComponentPropsWithoutRef<typeof CheckboxPrimitive.Root> {}

const Checkbox = forwardRef<
    ElementRef<typeof CheckboxPrimitive.Root>,
    CheckboxProps
>(({ className, ...props }, ref) => (
    <CheckboxPrimitive.Root
        ref={ref}
        className={cn(
            'peer h-4 w-4 shrink-0 rounded-sm border border-primary shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground',
            className
        )}
        {...props}
    >
        <CheckboxPrimitive.Indicator
            className={cn('flex items-center justify-center text-current')}
        >
            <CheckIcon className='h-4 w-4' />
        </CheckboxPrimitive.Indicator>
    </CheckboxPrimitive.Root>
))
Checkbox.displayName = CheckboxPrimitive.Root.displayName

export default Checkbox
