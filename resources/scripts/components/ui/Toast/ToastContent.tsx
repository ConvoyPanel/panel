import { cn } from '@/utils'
import { Toast as ToastPrimitive } from '@base-ui/react/toast'

const ToastContent = ({
    className,
    ...props
}: ToastPrimitive.Content.Props) => (
    <ToastPrimitive.Content
        data-slot={'toast-content'}
        // Only the frontmost card shows its contents; the ones behind it fade to
        // a bare surface until the stack is expanded.
        className={cn(
            'flex h-full items-center gap-3 overflow-hidden p-4 transition-opacity duration-250 ease-[cubic-bezier(0.22,1,0.36,1)] data-behind:opacity-0 data-expanded:opacity-100',
            className
        )}
        {...props}
    />
)
ToastContent.displayName = 'ToastContent'

export default ToastContent
