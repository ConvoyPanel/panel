import { cn } from '@/utils'
import { Drawer as DrawerPrimitive } from '@base-ui/react/drawer'

const DrawerDescription = ({
    className,
    ...props
}: DrawerPrimitive.Description.Props) => (
    <DrawerPrimitive.Description
        data-slot={'drawer-description'}
        className={cn('text-sm text-muted-foreground', className)}
        {...props}
    />
)
DrawerDescription.displayName = 'DrawerDescription'

export default DrawerDescription
