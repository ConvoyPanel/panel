import { cn } from '@/utils'
import { Command as CommandPrimitive } from 'cmdk'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

const Command = forwardRef<
    ElementRef<typeof CommandPrimitive>,
    ComponentPropsWithoutRef<typeof CommandPrimitive>
>(({ className, ...props }, ref) => (
    <CommandPrimitive
        ref={ref}
        data-slot={'command'}
        className={cn(
            'bg-popover text-popover-foreground flex size-full flex-col overflow-hidden rounded-xl p-1',
            className
        )}
        {...props}
    />
))
Command.displayName = CommandPrimitive.displayName

export default Command
