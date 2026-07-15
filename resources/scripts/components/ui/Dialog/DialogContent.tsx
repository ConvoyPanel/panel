import { cn } from '@/utils'
import { Dialog as DialogPrimitive } from '@base-ui/react/dialog'
import { IconX } from '@tabler/icons-react'

import { DialogOverlay, DialogPortal } from '@/components/ui/Dialog'

const DialogContent = ({
    className,
    children,
    ...props
}: DialogPrimitive.Popup.Props) => (
    <DialogPortal>
        <DialogOverlay />
        <DialogPrimitive.Popup
            data-slot={'dialog-content'}
            className={cn(
                // Values from the create-page default (base + style "nova"),
                // source apps/v4/styles/base-nova/ui/dialog.tsx: the flat
                // `ring-1 ring-foreground/10` on `rounded-xl bg-popover` instead
                // of border+shadow, and `p-4` rather than shadcn's older `p-6`.
                // Upstream caps at `sm:max-w-sm`; ours stay `sm:max-w-lg` because
                // these hold lists and forms, not just a confirm prompt.
                'fixed left-1/2 z-50 grid w-full max-w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2 gap-4 rounded-xl bg-popover p-4 text-sm text-popover-foreground ring-1 ring-foreground/10 outline-none sm:max-w-lg',
                // Depth cue, mirroring Base UI's own nested-dialogs demo. A
                // nested dialog renders NO backdrop of its own (Base UI hides it
                // so the parent shows through), so the parent itself has to
                // signal depth: per open descendant it slides down, scales back,
                // and tints. Sliding is what makes the stack legible — scale
                // alone leaves the parent completely hidden behind any child
                // taller than it is.
                //
                // --nested-dialogs is the open-descendant count, set inline on
                // every popup by Base UI, so it is always defined and resolves to
                // 0 (no offset, no scale, no tint) for an unnested dialog.
                'top-[calc(50%+1.25rem*var(--nested-dialogs,0))] scale-[calc(1-0.05*var(--nested-dialogs,0))]',
                'after:pointer-events-none after:absolute after:inset-0 after:rounded-[inherit] after:bg-foreground/5 after:opacity-0 after:transition-opacity after:duration-100 data-nested-dialog-open:after:opacity-100',
                // Base UI drives enter/exit with data-starting-style /
                // data-ending-style, so we keep transitions here rather than
                // nova's data-open:animate-in keyframes: a keyframed transform
                // would fight the nesting scale/offset for the same properties.
                // Tailwind v4 compiles the centring translate and the scale above
                // to the standalone `translate` / `scale` properties, so
                // `transform` stays `none` here and is not worth transitioning.
                'duration-100 transition-[opacity,scale,top] data-ending-style:scale-95 data-ending-style:opacity-0 data-starting-style:scale-95 data-starting-style:opacity-0',
                'motion-reduce:transition-none',
                className
            )}
            {...props}
        >
            {children}
            <DialogPrimitive.Close
                className={
                    'absolute top-4 right-4 flex size-5 items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-3 focus-visible:ring-ring/50 disabled:pointer-events-none'
                }
            >
                <IconX className={'size-4'} />
                <span className={'sr-only'}>Close</span>
            </DialogPrimitive.Close>
        </DialogPrimitive.Popup>
    </DialogPortal>
)
DialogContent.displayName = 'DialogContent'

export default DialogContent
