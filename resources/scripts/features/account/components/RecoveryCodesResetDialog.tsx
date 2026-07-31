import { useRecoveryCodesModalStore } from '@/features/account/components/RecoveryCodesContainer.tsx'
import {
    recoveryCodeQueries,
    regenerateRecoveryCodes,
} from '@/features/account/recovery-codes/api.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import { useQueryClient } from '@tanstack/react-query'

import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

const RecoveryCodesResetDialog = () => {
    const queryClient = useQueryClient()
    const { open, close } = useModal(useRecoveryCodesModalStore, 'reset')

    const [state, reset] = useAsyncFunction(async () => {
        try {
            await regenerateRecoveryCodes()

            await queryClient.invalidateQueries({
                queryKey: recoveryCodeQueries.codes().queryKey,
            })

            toast.add({ title: 'Recovery codes reset', type: 'success' })

            // Closing this step reveals the parent, which is already showing the
            // codes — now the fresh ones. No separate reveal to queue up.
            close()
        } catch (e) {
            toast.add({
                title: 'Failed to reset recovery codes',
                type: 'error',
            })
            throw e
        }
    })

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Reset recovery codes
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        This replaces all eight codes with new ones. Any code
                        you have written down or saved will stop working,
                        whichever method you saved it for.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={<Button variant={'outline'}>Cancel</Button>}
                    />
                    <Button
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={reset}
                    >
                        Reset
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default RecoveryCodesResetDialog
