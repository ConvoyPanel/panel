import { useState } from 'react'

import AuthSetting from '@/features/account/components/AuthSetting.tsx'
import AuthenticatorDisableDialog from '@/features/account/components/AuthenticatorDisableDialog.tsx'
import AuthenticatorEnableDialog from '@/features/account/components/AuthenticatorEnableDialog.tsx'
import AuthenticatorRecoveryCodesDialog from '@/features/account/components/AuthenticatorRecoveryCodesDialog.tsx'
import AuthenticatorResetRecoveryCodesDialog from '@/features/account/components/AuthenticatorResetRecoveryCodesDialog.tsx'
import AuthenticatorStatus from '@/features/account/components/AuthenticatorStatus.tsx'

import AuthDialog from '@/components/ui/Dialog/AuthDialog.tsx'

import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'

const AuthenticatorMainDialog = () => {
    const [open, setOpen] = useState(false)

    return (
        <ResponsiveDialog open={open} onOpenChange={setOpen}>
            <ResponsiveDialogTrigger
                render={
                    <AuthSetting
                        title={'Authenticator'}
                        description={'Time-based verification codes using an app'}
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

                {/* Nested inside the parent's content on purpose: Base UI gives
                    these no backdrop of their own, so the parent stays visible
                    (scaled back) underneath instead of being torn down and
                    rebuilt between steps. */}
                <AuthDialog onCancel={() => setOpen(false)} />
                <AuthenticatorEnableDialog />
                <AuthenticatorDisableDialog />
                <AuthenticatorRecoveryCodesDialog />
                <AuthenticatorResetRecoveryCodesDialog />
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AuthenticatorMainDialog
