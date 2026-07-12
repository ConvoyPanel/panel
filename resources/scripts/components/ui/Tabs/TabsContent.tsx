import { cn } from '@/utils'
import { Tabs } from '@base-ui/react/tabs'
import { forwardRef } from 'react'

const TabsContent = forwardRef<HTMLDivElement, Tabs.Panel.Props>(
    ({ className, ...props }, ref) => (
        <Tabs.Panel
            ref={ref}
            className={cn(
                'ring-offset-background focus-visible:ring-ring mt-2 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
                className
            )}
            {...props}
        />
    )
)
TabsContent.displayName = 'TabsContent'

export default TabsContent
