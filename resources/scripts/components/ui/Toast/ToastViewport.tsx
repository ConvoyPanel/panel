import { cn } from '@/utils'
import { Toast as ToastPrimitive } from '@base-ui/react/toast'

const ToastViewport = ({
    className,
    ...props
}: ToastPrimitive.Viewport.Props) => (
    <ToastPrimitive.Viewport
        data-slot={'toast-viewport'}
        // Upstream sits at z-50, which ties with the dialog and sheet layers —
        // and the Toaster mounts at the root, so its portal loses that tie and
        // the toast ends up under the dialog scrim. Most toasts here are raised
        // from inside a modal, so it is lifted clear of both.
        className={cn(
            'pointer-events-none fixed inset-x-4 bottom-4 z-[70] mx-auto w-auto max-w-sm outline-none sm:right-4 sm:left-auto sm:mx-0 sm:w-full',
            className
        )}
        {...props}
    />
)
ToastViewport.displayName = 'ToastViewport'

export default ToastViewport
