import { ConfirmDialog } from '@/components/ui/AlertDialog/index.ts'
import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'

const ConfirmDialogProvider = () => {
    const { isOpen, setIsOpen, options, handleConfirm, handleCancel } =
        useConfirmationStore()

    return (
        <ConfirmDialog
            isOpen={isOpen}
            onOpenChange={setIsOpen}
            config={options}
            onConfirm={handleConfirm}
            onCancel={handleCancel}
        />
    )
}

export default ConfirmDialogProvider
