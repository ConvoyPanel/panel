import { ComponentProps } from 'react'
import { Drawer as DrawerPrimitive } from 'vaul'

const Drawer = ({
    shouldScaleBackground = true,
    ...props
}: ComponentProps<typeof DrawerPrimitive.Root>) => (
    <DrawerPrimitive.Root
        shouldScaleBackground={shouldScaleBackground}
        {...props}
    />
)
Drawer.displayName = 'Drawer'

export default Drawer
