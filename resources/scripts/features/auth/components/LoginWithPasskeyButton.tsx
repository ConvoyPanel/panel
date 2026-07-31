import {
    getPasskeyAuthenticationOptions,
    verifyPasskeyAuthentication,
} from '@/features/auth/api.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import { startAuthentication } from '@simplewebauthn/browser'
import {
    AuthenticationResponseJSON,
    PublicKeyCredentialRequestOptionsJSON,
} from '@simplewebauthn/browser'
import { IconKey } from '@tabler/icons-react'
import { useNavigate } from '@tanstack/react-router'

import { Button } from '@/components/ui/Button'
import { toast } from '@/components/ui/Toast'

interface Props {
    redirectTo?: string
}

const LoginWithPasskeyButton = ({ redirectTo }: Props) => {
    const navigate = useNavigate()

    const [state, login] = useAsyncFunction(async () => {
        let optionsJSON: PublicKeyCredentialRequestOptionsJSON
        try {
            optionsJSON = await getPasskeyAuthenticationOptions()
        } catch (e) {
            toast.add({
                title: 'Failed to get passkey authentication options',
                type: 'error',
            })
            throw e
        }

        let authResponse: AuthenticationResponseJSON
        try {
            authResponse = await startAuthentication({ optionsJSON })
        } catch (e) {
            toast.add({ title: 'Authentication failed', type: 'error' })
            throw e
        }

        try {
            await verifyPasskeyAuthentication(authResponse)
        } catch (e) {
            toast.add({
                title: 'Invalid passkey. Please try again.',
                type: 'error',
            })
            throw e
        }

        await navigate({
            to: redirectTo ? `/${redirectTo.slice(1)}` : '/',
        })
    })

    return (
        <Button
            onClick={login}
            className={'w-full'}
            variant='outline'
            loading={state.loading}
        >
            <IconKey className='size-4' />
            Passkeys
        </Button>
    )
}

export default LoginWithPasskeyButton
