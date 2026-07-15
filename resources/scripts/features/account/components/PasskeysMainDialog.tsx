import useAsyncFunction from '@/hooks/use-async-function.ts'
import { startRegistration } from '@simplewebauthn/browser'
import { useState } from 'react'
import { toast } from 'sonner'

import {
    getRegistrationOptions,
    verifyRegistration,
} from '@/features/account/passkeys/api.ts'

import AuthSetting from '@/features/account/components/AuthSetting.tsx'
import PasskeyDeleteDialog from '@/features/account/components/PasskeyDeleteDialog.tsx'
import PasskeyList from '@/features/account/components/PasskeyList.tsx'
import PasskeyRenameDialog from '@/features/account/components/PasskeyRenameDialog.tsx'
import { usePasskeysModalStore } from '@/features/account/components/PasskeysContainer.tsx'

import AuthDialog from '@/components/ui/Dialog/AuthDialog.tsx'

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

const PasskeysMainDialog = () => {
    const [isMainDialogOpen, setMainDialogOpen] = useState(false)
    const openModal = usePasskeysModalStore(state => state.openModal)

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
                {/* Nested inside the parent's content: Base UI gives these no
                    backdrop of their own, so the parent stays visible (scaled
                    back) underneath rather than being torn down between steps. */}
                <AuthDialog onCancel={() => setMainDialogOpen(false)} />
                <PasskeyRenameDialog />
                <PasskeyDeleteDialog />
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default PasskeysMainDialog
