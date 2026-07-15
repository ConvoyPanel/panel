import useAsyncFunction from '@/hooks/use-async-function.ts'
import { startRegistration } from '@simplewebauthn/browser'
import { toast } from 'sonner'
import { useShallow } from 'zustand/react/shallow'

import {
    getRegistrationOptions,
    verifyRegistration,
} from '@/features/account/passkeys/api.ts'

import AuthSetting from '@/features/account/components/AuthSetting.tsx'
import PasskeyList from '@/features/account/components/PasskeyList.tsx'
import { usePasskeysModalStore } from '@/features/account/components/PasskeysContainer.tsx'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
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

const PasskeysContainer = () => {
    const [isMainDialogOpen, openModal, closeModal] = usePasskeysModalStore(
        useShallow(state => [
            state.activeModal === 'main',
            state.openModal,
            state.closeModal,
        ])
    )

    const [state, register] = useAsyncFunction(async () => {
        try {
            const optionsJSON = await getRegistrationOptions()

            const registrationData = await startRegistration({ optionsJSON })

            const passkey = await verifyRegistration(registrationData)

            openModal('rename', passkey)

            toast.success('Passkey added')
        } catch (e) {
            let message = 'Registration failed'
            if (e instanceof Error && e.name === 'InvalidStateError') {
                message = 'This authenticator is already registered'
            }

            toast.error(message)
            throw e
        }
    })

    const handleOpenChange = (open: boolean) => {
        if (open) {
            openModal('main')
        } else {
            closeModal('main')
        }
    }

    return (
        <ResponsiveDialog open={isMainDialogOpen} onOpenChange={handleOpenChange}>
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
                        'relative h-full max-h-[50vh] overflow-y-auto overflow-x-visible'
                    }
                >
                    <PasskeyList />
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={
                            <Button variant={'outline'}>Close</Button>
                        }
                    />

                    <Button
                        loading={state.loading}
                        onClick={register}
                        disabled={!window.isSecureContext}
                    >
                        Add passkey
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default PasskeysContainer
