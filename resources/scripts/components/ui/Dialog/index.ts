import { Dialog as DialogPrimitive } from '@base-ui/react/dialog'

import DialogContent from './DialogContent'
import DialogDescription from './DialogDescription'
import DialogFooter from './DialogFooter'
import DialogHeader from './DialogHeader'
import DialogOverlay from './DialogOverlay'
import DialogTitle from './DialogTitle'

const Dialog = DialogPrimitive.Root
const DialogPortal = DialogPrimitive.Portal
const DialogTrigger = DialogPrimitive.Trigger
const DialogClose = DialogPrimitive.Close

export {
    Dialog,
    DialogPortal,
    DialogOverlay,
    DialogTrigger,
    DialogClose,
    DialogContent,
    DialogHeader,
    DialogFooter,
    DialogTitle,
    DialogDescription,
}
