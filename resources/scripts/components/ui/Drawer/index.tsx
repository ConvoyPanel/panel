import { Drawer as DrawerPrimitive } from '@base-ui/react/drawer'

import Drawer from './Drawer'
import DrawerContent from './DrawerContext'
import DrawerDescription from './DrawerDescription'
import DrawerFooter from './DrawerFooter'
import DrawerHeader from './DrawerHeader'
import DrawerOverlay from './DrawerOverlay'
import DrawerTitle from './DrawerTitle'

const DrawerPortal = DrawerPrimitive.Portal
const DrawerTrigger = DrawerPrimitive.Trigger
const DrawerClose = DrawerPrimitive.Close

export {
    Drawer,
    DrawerPortal,
    DrawerOverlay,
    DrawerTrigger,
    DrawerClose,
    DrawerContent,
    DrawerHeader,
    DrawerFooter,
    DrawerTitle,
    DrawerDescription,
}
