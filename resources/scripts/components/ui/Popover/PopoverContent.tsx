import { cn } from '@/utils'
import { Popover as PopoverPrimitive } from '@base-ui/react/popover'
import { forwardRef } from 'react'

const PopoverContent = forwardRef<
    HTMLDivElement,
    PopoverPrimitive.Popup.Props &
        Pick<
            PopoverPrimitive.Positioner.Props,
            'align' | 'alignOffset' | 'side' | 'sideOffset'
        >
>(
    (
        {
            className,
            align = 'center',
            alignOffset = 0,
            side = 'bottom',
            sideOffset = 4,
            ...props
        },
        ref
    ) => (
        <PopoverPrimitive.Portal>
            <PopoverPrimitive.Positioner
                align={align}
                alignOffset={alignOffset}
                side={side}
                sideOffset={sideOffset}
                className={'isolate z-50'}
            >
                <PopoverPrimitive.Popup
                    ref={ref}
                    data-slot={'popover-content'}
                    className={cn(
                        'bg-popover text-popover-foreground ring-foreground/10 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 data-open:animate-in data-open:fade-in-0 data-open:zoom-in-95 data-closed:animate-out data-closed:fade-out-0 data-closed:zoom-out-95 z-50 flex w-72 origin-[var(--transform-origin)] flex-col gap-2.5 rounded-lg p-2.5 text-sm shadow-md ring-1 duration-100 outline-none',
                        className
                    )}
                    {...props}
                />
            </PopoverPrimitive.Positioner>
        </PopoverPrimitive.Portal>
    )
)
PopoverContent.displayName = 'PopoverContent'

export default PopoverContent
