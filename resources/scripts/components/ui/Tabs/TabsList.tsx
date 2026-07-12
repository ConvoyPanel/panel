import { cn } from '@/utils'
import { Tabs } from '@base-ui/react/tabs'
import { forwardRef } from 'react'

const TabsList = forwardRef<HTMLDivElement, Tabs.List.Props>(
    ({ className, activateOnFocus = true, ...props }, ref) => (
        <Tabs.List
            ref={ref}
            activateOnFocus={activateOnFocus}
            className={cn(
                'bg-muted text-muted-foreground inline-flex h-9 items-center justify-center rounded-lg p-1',
                className
            )}
            {...props}
        />
    )
)
TabsList.displayName = 'TabsList'

export default TabsList
