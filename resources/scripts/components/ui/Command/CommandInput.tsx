import { cn } from '@/utils'
import { IconSearch } from '@tabler/icons-react'
import { Command as CommandPrimitive } from 'cmdk'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

import { InputGroup, InputGroupAddon } from '@/components/ui/InputGroup'

const CommandInput = forwardRef<
    ElementRef<typeof CommandPrimitive.Input>,
    ComponentPropsWithoutRef<typeof CommandPrimitive.Input>
>(({ className, ...props }, ref) => (
    <div data-slot={'command-input-wrapper'} className={'p-1 pb-0'}>
        <InputGroup
            className={
                'border-input/30 bg-input/30 h-8 rounded-lg shadow-none [&_[data-slot=input-group-addon]]:pl-2'
            }
        >
            <InputGroupAddon>
                <IconSearch className={'size-4 shrink-0 opacity-50'} />
            </InputGroupAddon>
            <CommandPrimitive.Input
                ref={ref}
                data-slot={'input-group-control'}
                className={cn(
                    'placeholder:text-muted-foreground w-full bg-transparent text-sm outline-none disabled:cursor-not-allowed disabled:opacity-50',
                    className
                )}
                {...props}
            />
        </InputGroup>
    </div>
))

CommandInput.displayName = CommandPrimitive.Input.displayName

export default CommandInput
