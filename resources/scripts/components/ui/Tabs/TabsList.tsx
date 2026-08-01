import { cn } from '@/utils'
import { Tabs } from '@base-ui/react/tabs'
import { forwardRef } from 'react'

import TabsIndicator from './TabsIndicator'

const TabsList = forwardRef<HTMLDivElement, Tabs.List.Props>(
    // `activateOnFocus` is left at Base UI's default of `false`: arrow keys move
    // focus, and Enter/Space activates. It was forced to `true` here, which makes
    // merely arrowing onto a tab select it — fine for inert panels, but AuthDialog
    // fires a WebAuthn ceremony the moment its Passkey tab becomes active, so a
    // keyboard user could not read past the tab without a passkey prompt.
    ({ className, children, ...props }, ref) => (
        <Tabs.List
            ref={ref}
            className={cn(
                // Values from the create-page default (base + style "nova"):
                // h-8 with a p-[3px] inset. Upstream expresses the resting
                // colours through its cn-* CSS layer, which this repo does not
                // adopt, so bg-muted/text-muted-foreground stay as the local
                // equivalent. `relative` anchors the indicator slab.
                'relative inline-flex h-8 w-fit items-center justify-center rounded-lg bg-muted p-[3px] text-muted-foreground',
                className
            )}
            {...props}
        >
            {/* First in the DOM so the positioned triggers paint over it. */}
            <TabsIndicator />
            {children}
        </Tabs.List>
    )
)
TabsList.displayName = 'TabsList'

export default TabsList
