import { cn } from '@/utils'
import * as RadioGroupPrimitive from '@radix-ui/react-radio-group'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

const RadioGroup = forwardRef<
    ElementRef<typeof RadioGroupPrimitive.Root>,
    ComponentPropsWithoutRef<typeof RadioGroupPrimitive.Root>
>(({ className, ...props }, ref) => {
    return (
        <RadioGroupPrimitive.Root
            className={cn('grid gap-2', className)}
            {...props}
            ref={ref}
        />
    )
})
RadioGroup.displayName = RadioGroupPrimitive.Root.displayName

export default RadioGroup
