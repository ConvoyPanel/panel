import { cn } from '@/utils'
import { Toast as ToastPrimitive } from '@base-ui/react/toast'

const ToastTitle = ({ className, ...props }: ToastPrimitive.Title.Props) => (
    <ToastPrimitive.Title
        data-slot={'toast-title'}
        className={cn('text-sm font-medium', className)}
        {...props}
    />
)
ToastTitle.displayName = 'ToastTitle'

export default ToastTitle
