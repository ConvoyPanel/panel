import AuthSetting from '@/components/interfaces/Client/Security/AuthSetting.tsx'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { Separator } from '@/components/ui/Separator.tsx'


const AuthenticationCard = () => {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Authentication</CardTitle>
                <CardDescription>
                    Manage your account authentication settings
                </CardDescription>
            </CardHeader>
            <CardContent className={'space-y-3'}>
                <AuthSetting
                    title={'Password'}
                    description={'Change your account password'}
                    onClick={() => {}}
                />
                <Separator />
                <AuthSetting
                    title={'Authenticator'}
                    description={'Time-based verification codes using an app'}
                    onClick={() => {}}
                />
                <Separator />
                <AuthSetting
                    title={'Passkeys'}
                    description={
                        'Securely sign in with fingerprint, face, screen lock, or security key'
                    }
                    onClick={() => {}}
                />
            </CardContent>
        </Card>
    )
}

export default AuthenticationCard
