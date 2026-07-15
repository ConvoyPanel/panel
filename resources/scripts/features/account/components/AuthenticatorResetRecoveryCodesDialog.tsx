import useAsyncFunction from '@/hooks/use-async-function.ts'
import { toast } from 'sonner'
import { useShallow } from 'zustand/react/shallow'

import { regenerateRecoveryCodes } from '@/features/account/authenticator/api.ts'

import { useAuthenticatorModalStore } from '@/features/account/components/AuthenticatorContainer.tsx'

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

const AuthenticatorResetRecoveryCodesDialog = () => {
    const [open, closeModal, pushToQueue] = useAuthenticatorModalStore(
        useShallow(state => [
            state.activeModal === 'reset-recovery-codes',
            state.closeModal,
            state.pushToQueue,
        ])
    )

    const [state, reset] = useAsyncFunction(async () => {
        try {
            await regenerateRecoveryCodes()

            toast.success('Recovery codes reset')

            pushToQueue('recovery-codes')

            closeModal('reset-recovery-codes')
        } catch (e) {
            toast.error('Failed to reset recovery codes')
            throw e
        }
    })

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={open => !open && closeModal('reset-recovery-codes')}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Reset Recovery Codes</ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to reset your recovery codes? This
                        will invalidate all existing codes.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={
                            <Button variant={'outline'}>Cancel</Button>
                        }
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

export default AuthenticatorResetRecoveryCodesDialog
