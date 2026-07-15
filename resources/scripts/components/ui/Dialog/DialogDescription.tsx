import { cn } from '@/utils'
import { Dialog as DialogPrimitive } from '@base-ui/react/dialog'

const DialogDescription = ({
    className,
    ...props
}: DialogPrimitive.Description.Props) => (
    <DialogPrimitive.Description
        data-slot={'dialog-description'}
        // Values from the create-page default (base + style "nova").
        className={cn(
            'text-sm text-muted-foreground *:[a]:underline *:[a]:underline-offset-3 *:[a]:hover:text-foreground',
            className
        )}
        {...props}
    />
)
DialogDescription.displayName = 'DialogDescription'

export default DialogDescription
