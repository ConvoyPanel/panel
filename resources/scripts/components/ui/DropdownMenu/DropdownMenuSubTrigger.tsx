import { cn } from '@/utils'
import { Menu as MenuPrimitive } from '@base-ui/react/menu'
import { IconChevronRight } from '@tabler/icons-react'
import { forwardRef } from 'react'

const DropdownMenuSubTrigger = forwardRef<
    HTMLElement,
    MenuPrimitive.SubmenuTrigger.Props & { inset?: boolean }
>(({ className, inset, children, ...props }, ref) => (
    <MenuPrimitive.SubmenuTrigger
        ref={ref}
        data-slot={'dropdown-menu-sub-trigger'}
        data-inset={inset || undefined}
        className={cn(
            'focus:bg-accent focus:text-accent-foreground data-popup-open:bg-accent data-popup-open:text-accent-foreground data-open:bg-accent data-open:text-accent-foreground flex cursor-default items-center gap-1.5 rounded-md px-1.5 py-1 text-sm outline-none select-none data-[inset=true]:pl-7 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=size-])]:size-4',
            className
        )}
        {...props}
    >
        {children}
        <IconChevronRight className={'ml-auto'} />
    </MenuPrimitive.SubmenuTrigger>
))
DropdownMenuSubTrigger.displayName = 'DropdownMenuSubTrigger'

export default DropdownMenuSubTrigger
