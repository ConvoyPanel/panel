import { cn } from '@/utils'
import { Menu as MenuPrimitive } from '@base-ui/react/menu'
import { forwardRef } from 'react'

const DropdownMenuSubContent = forwardRef<
    HTMLDivElement,
    MenuPrimitive.Popup.Props &
        Pick<
            MenuPrimitive.Positioner.Props,
            'align' | 'alignOffset' | 'side' | 'sideOffset'
        >
>(
    (
        {
            align = 'start',
            alignOffset = -3,
            side = 'right',
            sideOffset = 0,
            className,
            ...props
        },
        ref
    ) => (
        <MenuPrimitive.Portal>
            <MenuPrimitive.Positioner
                className={'isolate z-50 outline-none'}
                align={align}
                alignOffset={alignOffset}
                side={side}
                sideOffset={sideOffset}
            >
                <MenuPrimitive.Popup
                    ref={ref}
                    data-slot={'dropdown-menu-sub-content'}
                    className={cn(
                        'bg-popover text-popover-foreground ring-foreground/10 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 data-open:animate-in data-open:fade-in-0 data-open:zoom-in-95 data-closed:animate-out data-closed:fade-out-0 data-closed:zoom-out-95 z-50 w-auto min-w-24 origin-[var(--transform-origin)] overflow-hidden rounded-lg p-1 shadow-lg ring-1 duration-100 outline-none',
                        className
                    )}
                    {...props}
                />
            </MenuPrimitive.Positioner>
        </MenuPrimitive.Portal>
    )
)
DropdownMenuSubContent.displayName = 'DropdownMenuSubContent'

export default DropdownMenuSubContent
