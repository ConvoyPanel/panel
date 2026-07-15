import { cn } from '@/utils'
import { Tabs } from '@base-ui/react/tabs'
import { forwardRef } from 'react'

const TabsTrigger = forwardRef<HTMLElement, Tabs.Tab.Props>(
    ({ className, ...props }, ref) => (
        <Tabs.Tab
            ref={ref}
            // Values from the create-page default (base + style "nova"), source
            // apps/v4/styles/base-nova/ui/tabs.tsx. The focus ring is the
            // ring-3/ring-ring/50 form the rest of the migrated primitives use;
            // the old `ring-2` + `ring-offset-2` + full-opacity `ring-ring` was
            // shadcn's pre-nova ring and read as a heavy dark halo on the active
            // tab. Upstream's variant/orientation `group-data-*` matrix is left
            // out — nothing here uses the line variant or vertical tabs.
            className={cn(
                'relative inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-transparent px-1.5 py-0.5 text-sm font-medium whitespace-nowrap text-foreground/60 transition-all',
                'hover:text-foreground data-active:bg-background data-active:text-foreground data-active:shadow-sm',
                'focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 focus-visible:outline-none',
                'disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50',
                'dark:text-muted-foreground dark:hover:text-foreground',
                "[&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
                className
            )}
            {...props}
        />
    )
)
TabsTrigger.displayName = 'TabsTrigger'

export default TabsTrigger
