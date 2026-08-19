import { cn } from '@/utils'
import { Collapsible as CollapsiblePrimitive } from '@base-ui/react/collapsible'
import { IconChevronRight } from '@tabler/icons-react'
import { ComponentProps } from 'react'

/**
 * Single disclosure section, Base UI backed.
 *
 * ⚠️ Base UI's state attributes are NOT Radix's. The trigger gets
 * `data-panel-open` (and *no* attribute at all when closed — the mapping returns
 * null); the panel gets `data-open`/`data-closed`. Radix's `data-state=open`
 * matches nothing here, and Tailwind never errors on a class that matches
 * nothing, so a wrong selector fails silently. Verified against the rendered DOM
 * and `@base-ui/react` 1.6.0's `utils/collapsibleOpenStateMapping`.
 */
const Collapsible = CollapsiblePrimitive.Root

const CollapsibleTrigger = ({
    className,
    children,
    ...props
}: ComponentProps<typeof CollapsiblePrimitive.Trigger>) => (
    <CollapsiblePrimitive.Trigger
        className={cn(
            'text-muted-foreground flex w-full items-center gap-1.5 rounded-lg py-1 text-sm font-medium transition-colors outline-none',
            'hover:text-foreground focus-visible:ring-ring/50 focus-visible:ring-3',
            // data-panel-open, not Radix's data-state=open.
            '[&[data-panel-open]>svg]:rotate-90',
            className
        )}
        {...props}
    >
        <IconChevronRight
            className={'size-4 shrink-0 transition-transform duration-200'}
            aria-hidden={'true'}
        />
        {children}
    </CollapsiblePrimitive.Trigger>
)

const CollapsiblePanel = ({
    className,
    children,
    ...props
}: ComponentProps<typeof CollapsiblePrimitive.Panel>) => (
    <CollapsiblePrimitive.Panel
        className={cn(
            // Base UI measures the panel and exposes its height as a CSS var;
            // the starting/ending styles are what make the transition run.
            'h-[var(--collapsible-panel-height)] overflow-hidden transition-[height] duration-200 ease-out',
            // The clip that makes the transition possible also crops the focus
            // ring of any control against the panel edge; `clip-slack` pads the
            // clip box and cancels it in layout. See app.css.
            'clip-slack',
            'data-[ending-style]:h-0 data-[starting-style]:h-0',
            'motion-reduce:transition-none',
            className
        )}
        {...props}
    >
        {children}
    </CollapsiblePrimitive.Panel>
)

export { Collapsible, CollapsibleTrigger, CollapsiblePanel }
