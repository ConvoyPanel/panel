import { cn } from '@/utils'
import { Toast as ToastPrimitive } from '@base-ui/react/toast'

import { Button } from '@/components/ui/Button'

const ToastAction = ({
    className,
    render = <Button variant={'outline'} size={'sm'} />,
    ...props
}: ToastPrimitive.Action.Props) => (
    <ToastPrimitive.Action
        data-slot={'toast-action'}
        render={render}
        className={cn('shrink-0', className)}
        {...props}
    />
)
ToastAction.displayName = 'ToastAction'

export default ToastAction
