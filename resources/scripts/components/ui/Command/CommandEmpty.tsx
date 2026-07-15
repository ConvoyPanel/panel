import { Command as CommandPrimitive } from 'cmdk'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

const CommandEmpty = forwardRef<
    ElementRef<typeof CommandPrimitive.Empty>,
    ComponentPropsWithoutRef<typeof CommandPrimitive.Empty>
>((props, ref) => (
    <CommandPrimitive.Empty
        ref={ref}
        data-slot={'command-empty'}
        className='py-6 text-center text-sm'
        {...props}
    />
))

CommandEmpty.displayName = CommandPrimitive.Empty.displayName

export default CommandEmpty
