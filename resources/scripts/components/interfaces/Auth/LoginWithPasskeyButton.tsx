import useAsyncFunction from '@/hooks/use-async-function.ts'
import { startAuthentication } from '@simplewebauthn/browser'
import {
    AuthenticationResponseJSON,
    PublicKeyCredentialRequestOptionsJSON,
} from '@simplewebauthn/browser'
import { IconKey } from '@tabler/icons-react'
import { useNavigate } from '@tanstack/react-router'
import { toast } from 'sonner'

import {
    getPasskeyAuthenticationOptions,
    verifyPasskeyAuthentication,
} from '@/features/auth/api.ts'

import { Button } from '@/components/ui/Button'

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
            toast.error('Failed to get passkey authentication options')
            throw e
        }

        let authResponse: AuthenticationResponseJSON
        try {
            authResponse = await startAuthentication({ optionsJSON })
        } catch (e) {
            toast.error('Authentication failed')
            throw e
        }

        try {
            await verifyPasskeyAuthentication(authResponse)
        } catch (e) {
            toast.error('Invalid passkey. Please try again.')
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
            <IconKey className='mr-2 h-4 w-4' />
            Passkeys
        </Button>
    )
}

export default LoginWithPasskeyButton
