import { Toast as ToastPrimitive } from '@base-ui/react/toast'

import Toast from './Toast'
import ToastAction from './ToastAction'
import ToastClose from './ToastClose'
import ToastContent from './ToastContent'
import ToastDescription from './ToastDescription'
import ToastIcon from './ToastIcon'
import ToastTitle from './ToastTitle'
import ToastViewport from './ToastViewport'
import toast from './toast-manager'

const ToastList = () => {
    const { toasts } = ToastPrimitive.useToastManager()

    return toasts.map(item => (
        <Toast key={item.id} toast={item}>
            <ToastContent>
                <ToastIcon type={item.type} />
                <div className={'flex min-w-0 flex-1 flex-col gap-1'}>
                    <ToastTitle />
                    <ToastDescription />
                </div>
                <ToastAction />
                <ToastClose />
            </ToastContent>
        </Toast>
    ))
}

// Mount once at the root. It has no required children — the module-level manager
// is what connects `toast.add()` from anywhere in the app to this viewport.
const Toaster = ({
    children,
    toastManager = toast,
    ...props
}: ToastPrimitive.Provider.Props) => (
    <ToastPrimitive.Provider toastManager={toastManager} {...props}>
        {children}
        <ToastPrimitive.Portal data-slot={'toast-portal'}>
            <ToastViewport>
                <ToastList />
            </ToastViewport>
        </ToastPrimitive.Portal>
    </ToastPrimitive.Provider>
)
Toaster.displayName = 'Toaster'

export default Toaster
