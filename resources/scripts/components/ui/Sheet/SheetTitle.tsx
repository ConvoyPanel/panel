import { cn } from '@/utils'
import { Dialog as SheetPrimitive } from '@base-ui/react/dialog'

const SheetTitle = ({ className, ...props }: SheetPrimitive.Title.Props) => (
    <SheetPrimitive.Title
        data-slot={'sheet-title'}
        className={cn('text-lg font-semibold text-foreground', className)}
        {...props}
    />
)
SheetTitle.displayName = 'SheetTitle'

export default SheetTitle
