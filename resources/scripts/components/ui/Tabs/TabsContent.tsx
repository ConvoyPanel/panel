import { cn } from '@/utils'
import { Tabs } from '@base-ui/react/tabs'
import { forwardRef } from 'react'

const TabsContent = forwardRef<HTMLDivElement, Tabs.Panel.Props>(
    ({ className, ...props }, ref) => (
        <Tabs.Panel
            ref={ref}
            // Values from the create-page default (base + style "nova"). Keeps
            // the mt-2 gap: nova gets its spacing from the Tabs root's `gap-2`,
            // which this wrapper does not set.
            className={cn('mt-2 flex-1 text-sm outline-none', className)}
            {...props}
        />
    )
)
TabsContent.displayName = 'TabsContent'

export default TabsContent
