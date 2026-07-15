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
                'fixed top-[50%] left-[50%] z-50 grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 border bg-background p-6 shadow-lg duration-200 sm:rounded-lg',
                'transition-[opacity,transform,scale] data-ending-style:scale-95 data-ending-style:opacity-0 data-starting-style:scale-95 data-starting-style:opacity-0',
                // A nested dialog renders NO backdrop of its own (Base UI hides
                // it so the parent stays visible underneath), so the parent is
                // what signals depth: it scales back per level of nesting.
                // --nested-dialogs is the open-descendant count.
                'data-nested-dialog-open:scale-[calc(1-0.05*var(--nested-dialogs))]',
                'motion-reduce:transition-none',
                className
            )}
            {...props}
        >
            {children}
            <DialogPrimitive.Close
                className={
                    'absolute top-4 right-4 rounded-sm opacity-70 ring-offset-background transition-opacity outline-none hover:opacity-100 focus-visible:ring-3 focus-visible:ring-ring/50 disabled:pointer-events-none'
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
