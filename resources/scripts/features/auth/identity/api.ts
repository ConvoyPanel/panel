import type {
    AuthenticationResponseJSON,
    PublicKeyCredentialRequestOptionsJSON,
} from '@simplewebauthn/browser'

import { apiFetch } from '@/lib/api'
import ConfirmableIdentityController from '@/wayfinder/actions/App/Http/Controllers/Auth/ConfirmableIdentityController'

interface Payload {
    passkey?: AuthenticationResponseJSON | null
    password?: string | null
}

export const confirmIdentity = async ({
    passkey,
    password,
}: Payload): Promise<void> => {
    await apiFetch(ConfirmableIdentityController.store(), {
        body: {
            passkey: passkey ? JSON.stringify(passkey) : undefined,
            password,
        },
    })
}

export const getPasskeyAuthenticationOptions =
    (): Promise<PublicKeyCredentialRequestOptionsJSON> =>
        apiFetch<PublicKeyCredentialRequestOptionsJSON>(
            ConfirmableIdentityController.generatePasskeyAuthOptions()
        )
