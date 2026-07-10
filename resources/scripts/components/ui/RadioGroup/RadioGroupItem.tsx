import { cn } from '@/utils'
import { DotFilledIcon } from '@radix-ui/react-icons'
import * as RadioGroupPrimitive from '@radix-ui/react-radio-group'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

const RadioGroupItem = forwardRef<
    ElementRef<typeof RadioGroupPrimitive.Item>,
    ComponentPropsWithoutRef<typeof RadioGroupPrimitive.Item>
>(({ className, ...props }, ref) => {
    return (
        <RadioGroupPrimitive.Item
            ref={ref}
            className={cn(
                'aspect-square h-4 w-4 rounded-full border border-primary text-primary shadow-xs focus:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                className
            )}
            {...props}
        >
            <RadioGroupPrimitive.Indicator className='flex items-center justify-center'>
                <DotFilledIcon className='h-3.5 w-3.5 fill-primary' />
            </RadioGroupPrimitive.Indicator>
        </RadioGroupPrimitive.Item>
    )
})
RadioGroupItem.displayName = RadioGroupPrimitive.Item.displayName

export default RadioGroupItem
