import { cn } from '@/utils'
import { Menu as MenuPrimitive } from '@base-ui/react/menu'
import { forwardRef, isValidElement } from 'react'

const DropdownMenuItem = forwardRef<
    HTMLElement,
    MenuPrimitive.Item.Props & {
        inset?: boolean
        variant?: 'default' | 'destructive'
        asChild?: boolean
    }
>(
    (
        { className, inset, variant = 'default', asChild, children, ...props },
        ref
    ) => (
        <MenuPrimitive.Item
            ref={ref}
            data-slot={'dropdown-menu-item'}
            data-inset={inset || undefined}
            data-variant={variant}
            render={asChild && isValidElement(children) ? children : undefined}
            className={cn(
                'group/dropdown-menu-item focus:bg-accent focus:text-accent-foreground data-[variant=destructive]:text-destructive data-[variant=destructive]:focus:bg-destructive/10 data-[variant=destructive]:focus:text-destructive dark:data-[variant=destructive]:focus:bg-destructive/20 data-[variant=destructive]:[&_svg]:text-destructive relative flex cursor-default items-center gap-1.5 rounded-md px-1.5 py-1 text-sm outline-none select-none data-disabled:pointer-events-none data-disabled:opacity-50 data-[inset=true]:pl-7 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=size-])]:size-4',
                className
            )}
            {...props}
        >
            {asChild ? undefined : children}
        </MenuPrimitive.Item>
    )
)
DropdownMenuItem.displayName = 'DropdownMenuItem'

export default DropdownMenuItem
