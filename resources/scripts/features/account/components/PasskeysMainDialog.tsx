import { getRecoveryCodes } from '@/features/account/authenticator/api.ts'
import AuthSetting from '@/features/account/components/AuthSetting.tsx'
import PasskeyDeleteDialog from '@/features/account/components/PasskeyDeleteDialog.tsx'
import PasskeyList from '@/features/account/components/PasskeyList.tsx'
import PasskeyRecoveryCodesDialog from '@/features/account/components/PasskeyRecoveryCodesDialog.tsx'
import PasskeyRenameDialog from '@/features/account/components/PasskeyRenameDialog.tsx'
import { usePasskeysModalStore } from '@/features/account/components/PasskeysContainer.tsx'
import {
    getRegistrationOptions,
    passkeyQueries,
    usePasskeys,
    verifyRegistration,
} from '@/features/account/passkeys/api.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import { getApiErrorMessage } from '@/utils/http.ts'
import { startRegistration } from '@simplewebauthn/browser'
import { useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { toast } from 'sonner'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import AuthDialog from '@/components/ui/Dialog/AuthDialog.tsx'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'

const PasskeysMainDialog = () => {
    const queryClient = useQueryClient()
    const { data: passkeys } = usePasskeys()
    const [isMainDialogOpen, setMainDialogOpen] = useState(false)
    const [recoveryCodes, setRecoveryCodes] = useState<string[] | null>(null)
    const [registeredPasskey, setRegisteredPasskey] = useState<
        Awaited<ReturnType<typeof verifyRegistration>>['passkey'] | null
    >(null)
    const openModal = usePasskeysModalStore(state => state.openModal)

    const [state, register] = useAsyncFunction(async () => {
        try {
            const optionsJSON = await getRegistrationOptions()

            const registrationData = await startRegistration({ optionsJSON })

            const result = await verifyRegistration(registrationData)

            await queryClient.invalidateQueries({
                queryKey: passkeyQueries.all(),
            })

            if (result.recoveryCodes) {
                setRegisteredPasskey(result.passkey)
                setRecoveryCodes(result.recoveryCodes)
            } else {
                openModal('rename', result.passkey)
            }

            toast.success('Passkey added')
        } catch (e) {
            // The authenticator's own failures never reach the API, so they keep
            // a local message; everything else defers to what the server said —
            // it reports *why* a passkey was rejected, and a blanket
            // "Registration failed" left the user with nothing to act on.
            const message =
                e instanceof Error && e.name === 'InvalidStateError'
                    ? 'This authenticator is already registered'
                    : getApiErrorMessage(e, 'Registration failed')

            toast.error(message)
            throw e
        }
    })

    const [recoveryState, showRecoveryCodes] = useAsyncFunction(async () => {
        try {
            setRecoveryCodes(await getRecoveryCodes())
        } catch (e) {
            toast.error('Failed to load recovery codes')
            throw e
        }
    })

    const closeRecoveryCodes = () => {
        setRecoveryCodes(null)

        if (registeredPasskey) {
            openModal('rename', registeredPasskey)
            setRegisteredPasskey(null)
        }
    }

    return (
        <ResponsiveDialog
            open={isMainDialogOpen}
            onOpenChange={setMainDialogOpen}
        >
            <ResponsiveDialogTrigger
                render={
                    <AuthSetting
                        title={'Passkeys'}
                        description={
                            'Securely sign in with fingerprint, face, screen lock, or security key'
                        }
                    />
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Passkeys</ResponsiveDialogTitle>

                    <ResponsiveDialogDescription>
                        Securely sign in with fingerprint, face, screen lock, or
                        security key
                    </ResponsiveDialogDescription>

                    {!window.isSecureContext && (
                        <Alert variant={'destructive'}>
                            <AlertDescription>
                                This site is not operating in a secure context
                                (HTTPS or localhost), which is required for
                                passkey registration. Please ensure the site
                                uses HTTPS to enable this feature.
                            </AlertDescription>
                        </Alert>
                    )}
                </ResponsiveDialogHeader>

                <ResponsiveDialogBody
                    className={
                        'relative h-full max-h-[50vh] overflow-x-visible overflow-y-auto'
                    }
                >
                    <PasskeyList />
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={<Button variant={'outline'}>Close</Button>}
                    />

                    {passkeys && passkeys.length > 0 && (
                        <Button
                            variant={'outline'}
                            loading={recoveryState.loading}
                            onClick={showRecoveryCodes}
                        >
                            Recovery codes
                        </Button>
                    )}

                    <Button
                        loading={state.loading}
                        onClick={register}
                        disabled={!window.isSecureContext}
                    >
                        Add passkey
                    </Button>
                </ResponsiveDialogFooter>
                {/* Nested inside the parent's content: Base UI gives these no
                    backdrop of their own, so the parent stays visible (scaled
                    back) underneath rather than being torn down between steps. */}
                <AuthDialog onCancel={() => setMainDialogOpen(false)} />
                <PasskeyRenameDialog />
                <PasskeyDeleteDialog />
                <PasskeyRecoveryCodesDialog
                    codes={recoveryCodes}
                    onClose={closeRecoveryCodes}
                />
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default PasskeysMainDialog
