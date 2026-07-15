import { cn } from '@/utils'
import { Dialog as DialogPrimitive } from '@base-ui/react/dialog'

const DialogTitle = ({ className, ...props }: DialogPrimitive.Title.Props) => (
    <DialogPrimitive.Title
        data-slot={'dialog-title'}
        className={cn(
            'text-lg leading-none font-semibold tracking-tight',
            className
        )}
        {...props}
    />
)
DialogTitle.displayName = 'DialogTitle'

export default DialogTitle
