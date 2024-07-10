import { useToast } from '@/hooks/use-toast.ts'

import {
    Toast,
    ToastClose,
    ToastDescription,
    ToastProvider,
    ToastTitle,
    ToastViewport,
} from '@/components/ui/Toast/index.ts'

const Toaster = () => {
    const { toasts } = useToast()

    return (
        <ToastProvider>
            {toasts.map(function ({
                id,
                title,
                description,
                action,
                ...props
            }) {
                return (
                    <Toast key={id} {...props}>
                        <div className='grid gap-1'>
                            {title && <ToastTitle>{title}</ToastTitle>}
                            {description && (
                                <ToastDescription>
                                    {description}
                                </ToastDescription>
                            )}
                        </div>
                        {action}
                        <ToastClose />
                    </Toast>
                )
            })}
            <ToastViewport />
        </ToastProvider>
    )
}

export default Toaster
