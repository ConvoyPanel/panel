import AuthenticatorContainer from '@/features/account/components/AuthenticatorContainer.tsx'
import PasskeysContainer from '@/features/account/components/PasskeysContainer.tsx'
import PasswordChangeDialog from '@/features/account/components/PasswordChangeDialog.tsx'
import RecoveryCodesContainer from '@/features/account/components/RecoveryCodesContainer.tsx'
import { useUser } from '@/features/auth/api.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'

const AuthenticationCard = () => {
    const { data: user } = useUser()

    // Dropped rather than shown disabled, unlike the profile fields: the row is
    // only ever a way in to the dialog, so a version of it that opens nothing
    // has nothing left to say. The rest of the card still stands — a locked
    // password does not mean a locked second factor.
    const canChangePassword = user?.accountCapabilities.canChangePassword ?? true

    return (
        <Card>
            <CardHeader>
                <CardTitle>Authentication</CardTitle>
            </CardHeader>
            <CardContent className={'flex flex-col gap-3'}>
                {canChangePassword && <PasswordChangeDialog />}
                <AuthenticatorContainer />
                <PasskeysContainer />
                {/* Renders nothing until a second factor exists to recover to.
                    Its own row rather than a button inside the other two: one
                    set of codes backs both, so it is a peer of them, not a
                    detail of either. */}
                <RecoveryCodesContainer />
            </CardContent>
        </Card>
    )
}

export default AuthenticationCard
