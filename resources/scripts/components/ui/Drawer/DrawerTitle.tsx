import { cn } from '@/utils'
import { Drawer as DrawerPrimitive } from '@base-ui/react/drawer'

const DrawerTitle = ({ className, ...props }: DrawerPrimitive.Title.Props) => (
    <DrawerPrimitive.Title
        data-slot={'drawer-title'}
        // Matches the nova DialogTitle so a responsive dialog reads the same at
        // both breakpoints.
        className={cn('text-base leading-none font-medium', className)}
        {...props}
    />
)
DrawerTitle.displayName = 'DrawerTitle'

export default DrawerTitle
