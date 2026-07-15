import { cn } from '@/utils'
import { Drawer as DrawerPrimitive } from '@base-ui/react/drawer'

const DrawerTitle = ({ className, ...props }: DrawerPrimitive.Title.Props) => (
    <DrawerPrimitive.Title
        data-slot={'drawer-title'}
        className={cn(
            'text-lg leading-none font-semibold tracking-tight',
            className
        )}
        {...props}
    />
)
DrawerTitle.displayName = 'DrawerTitle'

export default DrawerTitle
