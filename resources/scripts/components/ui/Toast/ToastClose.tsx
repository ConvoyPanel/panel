import { cn } from '@/utils'
import { Toast as ToastPrimitive } from '@base-ui/react/toast'
import { IconX } from '@tabler/icons-react'

import { Button } from '@/components/ui/Button'

const ToastClose = ({
    className,
    children,
    render = <Button variant={'ghost'} size={'icon'} className={'size-7'} />,
    ...props
}: ToastPrimitive.Close.Props) => (
    <ToastPrimitive.Close
        data-slot={'toast-close'}
        aria-label={'Close toast'}
        render={render}
        // The pseudo-element widens the hit target past the 28px button without
        // pushing the row layout around.
        className={cn(
            "text-muted-foreground hover:text-foreground relative shrink-0 after:absolute after:-inset-2 after:content-['']",
            className
        )}
        {...props}
    >
        {children ?? <IconX aria-hidden={'true'} />}
    </ToastPrimitive.Close>
)
ToastClose.displayName = 'ToastClose'

export default ToastClose
