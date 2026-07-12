import { cn } from '@/utils'
import { Tabs } from '@base-ui/react/tabs'
import { forwardRef } from 'react'

const TabsTrigger = forwardRef<HTMLElement, Tabs.Tab.Props>(
    ({ className, ...props }, ref) => (
        <Tabs.Tab
            ref={ref}
            className={cn(
                'ring-offset-background focus-visible:ring-ring data-active:bg-background data-active:text-foreground inline-flex items-center justify-center rounded-md px-3 py-1 text-sm font-medium whitespace-nowrap transition-all focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 data-active:shadow',
                className
            )}
            {...props}
        />
    )
)
TabsTrigger.displayName = 'TabsTrigger'

export default TabsTrigger
