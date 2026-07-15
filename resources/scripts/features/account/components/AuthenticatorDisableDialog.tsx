import useAsyncFunction from '@/hooks/use-async-function.ts'
import { toast } from 'sonner'
import { useQueryClient } from '@tanstack/react-query'
import { useShallow } from 'zustand/react/shallow'

import {
    disableAuthenticator,
    authenticatorQueries,
} from '@/features/account/authenticator/api.ts'

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

const AuthenticatorDisableDialog = () => {
    const queryClient = useQueryClient()
    const [open, closeModal] = useAuthenticatorModalStore(
        useShallow(state => [state.activeModal === 'disable', state.closeModal])
    )

    const [state, disable] = useAsyncFunction(async () => {
        try {
            await disableAuthenticator()

            await queryClient.invalidateQueries({
                queryKey: authenticatorQueries.enabled().queryKey,
            })

            toast.success('Authenticator disabled')

            closeModal('disable')
        } catch (e) {
            toast.error('Failed to disable authenticator')
            throw e
        }
    })

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={open => !open && closeModal('disable')}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Disable Authenticator</ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to disable the authenticator for
                        your account?
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
