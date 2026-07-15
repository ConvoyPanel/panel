import { Suspense, lazy, useEffect, useState } from 'react'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'

const ConfirmDialog = lazy(() => import('./ConfirmDialog.tsx'))

const ConfirmDialogProvider = () => {
    const [activated, setActivated] = useState(false)
    const { isOpen, setIsOpen, options, handleConfirm, handleCancel } =
        useConfirmationStore()

    useEffect(() => {
        if (isOpen) {
            setActivated(true)
        }
    }, [isOpen])

    return (
        activated && (
            <Suspense fallback={null}>
                <ConfirmDialog
                    isOpen={isOpen}
                    onOpenChange={setIsOpen}
                    config={options}
                    onConfirm={handleConfirm}
                    onCancel={handleCancel}
                />
            </Suspense>
        )
    )
}

export default ConfirmDialogProvider
