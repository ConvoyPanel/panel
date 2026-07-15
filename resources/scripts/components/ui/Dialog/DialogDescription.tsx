import { cn } from '@/utils'
import { Dialog as DialogPrimitive } from '@base-ui/react/dialog'

const DialogDescription = ({
    className,
    ...props
}: DialogPrimitive.Description.Props) => (
    <DialogPrimitive.Description
        data-slot={'dialog-description'}
        className={cn('text-sm text-muted-foreground', className)}
        {...props}
    />
)
DialogDescription.displayName = 'DialogDescription'

export default DialogDescription
