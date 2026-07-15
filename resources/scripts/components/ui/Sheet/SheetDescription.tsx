import { cn } from '@/utils'
import { Dialog as SheetPrimitive } from '@base-ui/react/dialog'

const SheetDescription = ({
    className,
    ...props
}: SheetPrimitive.Description.Props) => (
    <SheetPrimitive.Description
        data-slot={'sheet-description'}
        className={cn('text-sm text-muted-foreground', className)}
        {...props}
    />
)
SheetDescription.displayName = 'SheetDescription'

export default SheetDescription
