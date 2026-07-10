import AuthenticatorContainer from '@/features/account/components/AuthenticatorContainer.tsx'
import PasskeysContainer from '@/features/account/components/PasskeysContainer.tsx'
import PasswordChangeDialog from '@/features/account/components/PasswordChangeDialog.tsx'

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
            </CardContent>
        </Card>
    )
}

export default AuthenticationCard
