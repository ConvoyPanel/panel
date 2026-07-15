import { cn } from '@/utils'
import { Menu as MenuPrimitive } from '@base-ui/react/menu'
import { forwardRef } from 'react'

const DropdownMenuSeparator = forwardRef<
    HTMLDivElement,
    MenuPrimitive.Separator.Props
>(({ className, ...props }, ref) => (
    <MenuPrimitive.Separator
        ref={ref}
        data-slot={'dropdown-menu-separator'}
        className={cn('bg-border -mx-1 my-1 h-px', className)}
        {...props}
    />
))
DropdownMenuSeparator.displayName = 'DropdownMenuSeparator'

export default DropdownMenuSeparator
