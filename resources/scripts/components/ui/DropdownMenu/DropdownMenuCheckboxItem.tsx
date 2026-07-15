import { cn } from '@/utils'
import { Menu as MenuPrimitive } from '@base-ui/react/menu'
import { IconCheck } from '@tabler/icons-react'
import { forwardRef } from 'react'

const DropdownMenuCheckboxItem = forwardRef<
    HTMLElement,
    MenuPrimitive.CheckboxItem.Props & { inset?: boolean }
>(({ className, children, checked, inset, ...props }, ref) => (
    <MenuPrimitive.CheckboxItem
        ref={ref}
        data-slot={'dropdown-menu-checkbox-item'}
        data-inset={inset || undefined}
        className={cn(
            'focus:bg-accent focus:text-accent-foreground relative flex cursor-default items-center gap-1.5 rounded-md py-1 pr-8 pl-1.5 text-sm outline-none select-none data-disabled:pointer-events-none data-disabled:opacity-50 data-[inset=true]:pl-7 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=size-])]:size-4',
            className
        )}
        checked={checked}
        {...props}
    >
        <span
            data-slot={'dropdown-menu-checkbox-item-indicator'}
            className={
                'pointer-events-none absolute right-2 flex items-center justify-center'
            }
        >
            <MenuPrimitive.CheckboxItemIndicator>
                <IconCheck />
            </MenuPrimitive.CheckboxItemIndicator>
        </span>
        {children}
    </MenuPrimitive.CheckboxItem>
))
DropdownMenuCheckboxItem.displayName = 'DropdownMenuCheckboxItem'

export default DropdownMenuCheckboxItem
