import { cn } from '@/utils'
import { Tabs } from '@base-ui/react/tabs'
import { forwardRef } from 'react'

// The active-tab slab. It used to live on the trigger itself as
// `data-active:bg-background`, which could only cross-fade between tabs; as a
// single element parked behind the list it slides and resizes to the tab being
// activated instead. Base UI hides it until the list has been measured, so the
// first paint lands in place rather than sliding in from the left edge.
const TabsIndicator = forwardRef<HTMLSpanElement, Tabs.Indicator.Props>(
    ({ className, ...props }, ref) => (
        <Tabs.Indicator
            ref={ref}
            className={cn(
                'absolute top-0 left-0 h-[var(--active-tab-height)] w-[var(--active-tab-width)]',
                'translate-x-[var(--active-tab-left)] translate-y-[var(--active-tab-top)]',
                'rounded-md bg-background shadow-sm',
                'transition-[translate,width,height] duration-200 ease-out motion-reduce:transition-none',
                className
            )}
            {...props}
        />
    )
)
TabsIndicator.displayName = 'TabsIndicator'

export default TabsIndicator
