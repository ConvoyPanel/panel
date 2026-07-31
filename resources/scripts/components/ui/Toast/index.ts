import { Toast as ToastPrimitive } from '@base-ui/react/toast'

import Toast from './Toast'
import ToastAction from './ToastAction'
import ToastClose from './ToastClose'
import ToastContent from './ToastContent'
import ToastDescription from './ToastDescription'
import ToastIcon from './ToastIcon'
import ToastTitle from './ToastTitle'
import ToastViewport from './ToastViewport'
import Toaster from './Toaster'
import toast from './toast-manager'

const ToastPortal = ToastPrimitive.Portal
const ToastProvider = ToastPrimitive.Provider
const createToastManager = ToastPrimitive.createToastManager
const useToastManager = ToastPrimitive.useToastManager

export {
    Toast,
    ToastAction,
    ToastClose,
    ToastContent,
    ToastDescription,
    ToastIcon,
    ToastPortal,
    ToastProvider,
    ToastTitle,
    ToastViewport,
    Toaster,
    createToastManager,
    toast,
    useToastManager,
}
