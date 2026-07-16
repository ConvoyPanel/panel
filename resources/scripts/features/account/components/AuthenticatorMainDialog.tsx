import { useIsAuthenticatorEnabled } from '@/features/account/authenticator/api.ts'
import AuthSetting from '@/features/account/components/AuthSetting.tsx'
import { useAuthenticatorModalStore } from '@/features/account/components/AuthenticatorContainer.tsx'
import AuthenticatorDisableDialog from '@/features/account/components/AuthenticatorDisableDialog.tsx'
import AuthenticatorEnableDialog from '@/features/account/components/AuthenticatorEnableDialog.tsx'
import AuthenticatorStatus from '@/features/account/components/AuthenticatorStatus.tsx'
import RecoveryCodesRevealDialog from '@/features/account/components/RecoveryCodesRevealDialog.tsx'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import { useState } from 'react'

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

const AuthenticatorMainDialog = () => {
    const [open, setOpen] = useState(false)
    // The codes the enable flow just minted, if any. Held here rather than
    // fetched by the reveal dialog: this is the one-time "save these", and
    // ongoing access belongs to the Recovery codes row on the security page.
    // This dialog no longer views or resets them — they were never an
    // authenticator-owned secret, and presenting them as one is what made the
    // account's single set read as two.
    const [newRecoveryCodes, setNewRecoveryCodes] = useState<string[] | null>(
        null
    )
    const { data: isEnabled } = useIsAuthenticatorEnabled()
    const openModal = useOpenModal(useAuthenticatorModalStore)

    return (
        <ResponsiveDialog open={open} onOpenChange={setOpen}>
            <ResponsiveDialogTrigger
                render={
                    <AuthSetting
                        title={'Authenticator'}
                        description={
                            'Time-based verification codes using an app'
                        }
                    />
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Authenticator</ResponsiveDialogTitle>

                    <ResponsiveDialogDescription>
                        Use an authenticator app to generate time-based
                        verification codes.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>

                <ResponsiveDialogBody>
                    <AuthenticatorStatus />
                </ResponsiveDialogBody>

                {/* Actions belong in the footer, like every other dialog in the
                    family. They used to float in the body under the status text
                    with hand-rolled spacing. */}
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={<Button variant={'outline'}>Close</Button>}
                    />
                    {isEnabled ? (
                        <Button
                            variant={'destructive'}
                            onClick={() => openModal('disable')}
                        >
                            Disable
                        </Button>
                    ) : (
                        <Button onClick={() => openModal('enable')}>
                            Enable
                        </Button>
                    )}
                </ResponsiveDialogFooter>

                {/* Nested inside the parent's content on purpose: Base UI gives
                    these no backdrop of their own, so the parent stays visible
                    (scaled back) underneath instead of being torn down and
                    rebuilt between steps. */}
                <AuthDialog onCancel={() => setOpen(false)} />
                <AuthenticatorEnableDialog onEnabled={setNewRecoveryCodes} />
                <AuthenticatorDisableDialog />
                <RecoveryCodesRevealDialog
                    codes={newRecoveryCodes}
                    onClose={() => setNewRecoveryCodes(null)}
                />
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AuthenticatorMainDialog
