import { Popover as PopoverPrimitive } from '@base-ui/react/popover'
import { forwardRef, isValidElement } from 'react'

import PopoverContent from './PopoverContent'

const Popover = PopoverPrimitive.Root

const PopoverTrigger = forwardRef<
    HTMLButtonElement,
    PopoverPrimitive.Trigger.Props & { asChild?: boolean }
>(({ asChild, children, ...props }, ref) => (
    <PopoverPrimitive.Trigger
        ref={ref}
        data-slot={'popover-trigger'}
        render={asChild && isValidElement(children) ? children : undefined}
        {...props}
    >
        {asChild ? undefined : children}
    </PopoverPrimitive.Trigger>
))
PopoverTrigger.displayName = 'PopoverTrigger'

export { Popover, PopoverTrigger, PopoverContent }
