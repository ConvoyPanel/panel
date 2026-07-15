import { cn } from '@/utils'
import { Dialog as DialogPrimitive } from '@base-ui/react/dialog'

const DialogTitle = ({ className, ...props }: DialogPrimitive.Title.Props) => (
    <DialogPrimitive.Title
        data-slot={'dialog-title'}
        // Values from the create-page default (base + style "nova"). Upstream
        // also carries `cn-font-heading`; that is the CSS-layer utility system
        // this repo deliberately did not adopt (see docs/card-design.md).
        className={cn('text-base leading-none font-medium', className)}
        {...props}
    />
)
DialogTitle.displayName = 'DialogTitle'

export default DialogTitle
