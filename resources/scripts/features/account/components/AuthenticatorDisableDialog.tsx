import {
    authenticatorQueries,
    disableAuthenticator,
} from '@/features/account/authenticator/api.ts'
import { useAuthenticatorModalStore } from '@/features/account/components/AuthenticatorContainer.tsx'
import { recoveryCodeQueries } from '@/features/account/recovery-codes/api.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'

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

const AuthenticatorDisableDialog = () => {
    const queryClient = useQueryClient()
    const { open, close } = useModal(useAuthenticatorModalStore, 'disable')

    const [state, disable] = useAsyncFunction(async () => {
        try {
            await disableAuthenticator()

            await queryClient.invalidateQueries({
                queryKey: authenticatorQueries.enabled().queryKey,
            })
            // Dropping the last second factor clears the account's recovery
            // codes, which retires the Recovery codes row.
            await queryClient.invalidateQueries({
                queryKey: recoveryCodeQueries.all(),
            })

            toast.success('Authenticator disabled')

            close()
        } catch (e) {
            toast.error('Failed to disable authenticator')
            throw e
        }
    })

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Disable Authenticator
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to disable the authenticator for
                        your account?
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={<Button variant={'outline'}>Cancel</Button>}
                    />
                    <Button
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={disable}
                    >
                        Disable
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AuthenticatorDisableDialog
