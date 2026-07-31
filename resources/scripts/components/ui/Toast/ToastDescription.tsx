import { cn } from '@/utils'
import { Toast as ToastPrimitive } from '@base-ui/react/toast'

const ToastDescription = ({
    className,
    ...props
}: ToastPrimitive.Description.Props) => (
    <ToastPrimitive.Description
        data-slot={'toast-description'}
        className={cn('text-muted-foreground text-sm', className)}
        {...props}
    />
)
ToastDescription.displayName = 'ToastDescription'

export default ToastDescription
