import { cn } from '@/utils'
import * as AccordionPrimitive from '@radix-ui/react-accordion'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

const AccordionContent = forwardRef<
    ElementRef<typeof AccordionPrimitive.Content>,
    ComponentPropsWithoutRef<typeof AccordionPrimitive.Content>
>(({ className, children, ...props }, ref) => (
    <AccordionPrimitive.Content
        ref={ref}
        // `clip-slack` keeps the height clip from cropping the focus ring of
        // controls sitting against the panel edge. See app.css.
        className='clip-slack data-[state=closed]:animate-accordion-up data-[state=open]:animate-accordion-down overflow-hidden text-sm'
        {...props}
    >
        <div className={cn('pt-0 pb-4', className)}>{children}</div>
    </AccordionPrimitive.Content>
))
AccordionContent.displayName = AccordionPrimitive.Content.displayName

export default AccordionContent
