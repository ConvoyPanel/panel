import * as TooltipPrimitive from '@radix-ui/react-tooltip'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

/**
 * Radix renders the trigger as a bare `<button>` with no `type`, which HTML
 * defaults to `submit`. A tooltip dropped inside a form — the node settings
 * status indicator, for one — then saves the form when it is clicked. Default
 * to `button`, leaving `asChild` alone so the attribute never lands on a child
 * that isn't a button.
 */
const TooltipTrigger = forwardRef<
    ElementRef<typeof TooltipPrimitive.Trigger>,
    ComponentPropsWithoutRef<typeof TooltipPrimitive.Trigger>
>(({ type, asChild, ...props }, ref) => (
    <TooltipPrimitive.Trigger
        ref={ref}
        asChild={asChild}
        type={asChild ? type : (type ?? 'button')}
        {...props}
    />
))
TooltipTrigger.displayName = TooltipPrimitive.Trigger.displayName

export default TooltipTrigger
