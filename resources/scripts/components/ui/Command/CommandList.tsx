import { cn } from '@/utils'
import { Command as CommandPrimitive } from 'cmdk'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

const CommandList = forwardRef<
    ElementRef<typeof CommandPrimitive.List>,
    ComponentPropsWithoutRef<typeof CommandPrimitive.List>
>(({ className, ...props }, ref) => (
    <CommandPrimitive.List
        ref={ref}
        data-slot={'command-list'}
        className={cn(
            'max-h-72 scroll-py-1 overflow-x-hidden overflow-y-auto outline-none',
            className
        )}
        {...props}
    />
))

CommandList.displayName = CommandPrimitive.List.displayName

export default CommandList
