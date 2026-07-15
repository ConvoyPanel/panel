import { Drawer as DrawerPrimitive } from '@base-ui/react/drawer'

const Drawer = (props: DrawerPrimitive.Root.Props) => (
    <DrawerPrimitive.Root swipeDirection={'down'} {...props} />
)
Drawer.displayName = 'Drawer'

export default Drawer
