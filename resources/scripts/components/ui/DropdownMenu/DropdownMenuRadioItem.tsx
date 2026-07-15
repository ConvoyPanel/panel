import { cn } from '@/utils'
import { Menu as MenuPrimitive } from '@base-ui/react/menu'
import { IconCheck } from '@tabler/icons-react'
import { forwardRef } from 'react'

const DropdownMenuRadioItem = forwardRef<
    HTMLElement,
    MenuPrimitive.RadioItem.Props & { inset?: boolean }
>(({ className, children, inset, ...props }, ref) => (
    <MenuPrimitive.RadioItem
        ref={ref}
        data-slot={'dropdown-menu-radio-item'}
        data-inset={inset || undefined}
        className={cn(
            'focus:bg-accent focus:text-accent-foreground relative flex cursor-default items-center gap-1.5 rounded-md py-1 pr-8 pl-1.5 text-sm outline-none select-none data-disabled:pointer-events-none data-disabled:opacity-50 data-[inset=true]:pl-7 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=size-])]:size-4',
            className
        )}
        {...props}
    >
        <span
            data-slot={'dropdown-menu-radio-item-indicator'}
            className={
                'pointer-events-none absolute right-2 flex items-center justify-center'
            }
        >
            <MenuPrimitive.RadioItemIndicator>
                <IconCheck />
            </MenuPrimitive.RadioItemIndicator>
        </span>
        {children}
    </MenuPrimitive.RadioItem>
))
DropdownMenuRadioItem.displayName = 'DropdownMenuRadioItem'

export default DropdownMenuRadioItem
