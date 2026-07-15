import { Dialog as SheetPrimitive } from '@base-ui/react/dialog'

import SheetContent, { type SheetContentProps } from './SheetContent'
import SheetDescription from './SheetDescription'
import SheetFooter from './SheetFooter'
import SheetHeader from './SheetHeader'
import SheetOverlay from './SheetOverlay'
import SheetTitle from './SheetTitle'

const Sheet = SheetPrimitive.Root
const SheetPortal = SheetPrimitive.Portal
const SheetTrigger = SheetPrimitive.Trigger
const SheetClose = SheetPrimitive.Close

export {
    Sheet,
    SheetPortal,
    SheetOverlay,
    SheetTrigger,
    SheetClose,
    SheetContent,
    type SheetContentProps,
    SheetHeader,
    SheetFooter,
    SheetTitle,
    SheetDescription,
}
