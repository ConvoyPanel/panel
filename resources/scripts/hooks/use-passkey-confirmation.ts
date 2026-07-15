import { getApiErrorMessage } from '@/utils/http.ts'
import { startAuthentication } from '@simplewebauthn/browser'
import {
    AuthenticationResponseJSON,
    PublicKeyCredentialRequestOptionsJSON,
} from '@simplewebauthn/browser'
import { useState } from 'react'

import {
    confirmIdentity,
    getPasskeyAuthenticationOptions,
} from '@/features/auth/identity/api.ts'


const usePasskeyConfirmation = () => {
    const [loading, setLoading] = useState(false)
    const confirm = async () => {
        setLoading(true)
        let optionsJSON: PublicKeyCredentialRequestOptionsJSON
        try {
            optionsJSON = await getPasskeyAuthenticationOptions()
        } catch (e) {
            setLoading(false)

            throw Error(
                getApiErrorMessage(e, 'Failed to get passkey authentication options')
            )
        }

        let authResponse: AuthenticationResponseJSON
        try {
            authResponse = await startAuthentication({ optionsJSON })
        } catch (e) {
            setLoading(false)

            throw Error('Authentication failed')
        }

        try {
            await confirmIdentity({ passkey: authResponse })
        } catch (e) {
            setLoading(false)

            // The server distinguishes an unrecognised passkey from one that
            // belongs to another account; don't flatten that back to one string.
            throw Error(getApiErrorMessage(e, 'Invalid passkey. Please try again.'))
        }

        setLoading(false)
    }

    return { loading, confirm }
}

export default usePasskeyConfirmation
