import { cn } from '@/utils'
import { Menu as MenuPrimitive } from '@base-ui/react/menu'
import { forwardRef } from 'react'

const DropdownMenuLabel = forwardRef<
    HTMLDivElement,
    MenuPrimitive.GroupLabel.Props & { inset?: boolean }
>(({ className, inset, ...props }, ref) => (
    <MenuPrimitive.GroupLabel
        ref={ref}
        data-slot={'dropdown-menu-label'}
        data-inset={inset || undefined}
        className={cn(
            'text-muted-foreground px-1.5 py-1 text-xs font-medium data-[inset=true]:pl-7',
            className
        )}
        {...props}
    />
))
DropdownMenuLabel.displayName = 'DropdownMenuLabel'

export default DropdownMenuLabel
