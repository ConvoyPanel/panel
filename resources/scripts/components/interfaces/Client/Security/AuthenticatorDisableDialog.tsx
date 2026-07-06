import useAsyncFunction from '@/hooks/use-async-function.ts'
import { toast } from 'sonner'
import { useQueryClient } from '@tanstack/react-query'
import { useShallow } from 'zustand/react/shallow'

import disableAuthenticator from '@/api/account/authenticator/disableAuthenticator.ts'
import { authenticatorQueries } from '@/api/account/authenticator/use-is-authenticator-enabled.ts'

import { useAuthenticatorModalStore } from '@/components/interfaces/Client/Security/AuthenticatorContainer.tsx'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaClose,
    CredenzaContent,
    CredenzaDescription,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'

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
        <Credenza
            open={open}
            onOpenChange={open => !open && closeModal('disable')}
        >
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Disable Authenticator</CredenzaTitle>
                    <CredenzaDescription>
                        Are you sure you want to disable the authenticator for
                        your account?
                    </CredenzaDescription>
                </CredenzaHeader>
                <CredenzaFooter>
                    <CredenzaClose asChild>
                        <Button variant={'outline'}>Cancel</Button>
                    </CredenzaClose>
                    <Button
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={disable}
                    >
                        Disable
                    </Button>
                </CredenzaFooter>
            </CredenzaContent>
        </Credenza>
    )
}

export default AuthenticatorDisableDialog
