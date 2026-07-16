import AuthenticatorContainer from '@/features/account/components/AuthenticatorContainer.tsx'
import PasskeysContainer from '@/features/account/components/PasskeysContainer.tsx'
import PasswordChangeDialog from '@/features/account/components/PasswordChangeDialog.tsx'
import RecoveryCodesContainer from '@/features/account/components/RecoveryCodesContainer.tsx'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'

const AuthenticationCard = () => {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Authentication</CardTitle>
                <CardDescription>
                    Manage your account authentication settings
                </CardDescription>
            </CardHeader>
            <CardContent className={'flex flex-col gap-3'}>
                <PasswordChangeDialog />
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
