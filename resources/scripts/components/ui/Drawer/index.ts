import { Drawer as DrawerPrimitive } from 'vaul'

import Drawer from './Drawer'
import DrawerContent from './DrawerContext'
import DrawerDescription from './DrawerDescription'
import DrawerFooter from './DrawerFooter'
import DrawerHeader from './DrawerHeader'
import DrawerOverlay from './DrawerOverlay'
import DrawerTitle from './DrawerTitle'


const DrawerTrigger = DrawerPrimitive.Trigger

const DrawerPortal = DrawerPrimitive.Portal

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
