import { cn } from '@/utils'
import { Menu as MenuPrimitive } from '@base-ui/react/menu'
import { forwardRef } from 'react'

const DropdownMenuContent = forwardRef<
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
            alignOffset = 0,
            side = 'bottom',
            sideOffset = 4,
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
                    data-slot={'dropdown-menu-content'}
                    className={cn(
                        'bg-popover text-popover-foreground ring-foreground/10 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 data-open:animate-in data-open:fade-in-0 data-open:zoom-in-95 data-closed:animate-out data-closed:fade-out-0 data-closed:zoom-out-95 z-50 max-h-[var(--available-height)] w-[var(--anchor-width)] min-w-32 origin-[var(--transform-origin)] overflow-x-hidden overflow-y-auto rounded-lg p-1 shadow-md ring-1 duration-100 outline-none data-closed:overflow-hidden',
                        className
                    )}
                    {...props}
                />
            </MenuPrimitive.Positioner>
        </MenuPrimitive.Portal>
    )
)
DropdownMenuContent.displayName = 'DropdownMenuContent'

export default DropdownMenuContent
