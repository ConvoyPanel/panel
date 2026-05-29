import { startAuthentication } from '@simplewebauthn/browser'
import {
    AuthenticationResponseJSON,
    PublicKeyCredentialRequestOptionsJSON,
} from '@simplewebauthn/browser'
import { useState } from 'react'

import confirmIdentity from '@/api/auth/identity/confirmIdentity.ts'
import getPasskeyAuthenticationOptions from '@/api/auth/identity/getPasskeyAuthenticationOptions.ts'


const usePasskeyConfirmation = () => {
    const [loading, setLoading] = useState(false)
    const confirm = async () => {
        setLoading(true)
        let optionsJSON: PublicKeyCredentialRequestOptionsJSON
        try {
            optionsJSON = await getPasskeyAuthenticationOptions()
        } catch (e) {
            setLoading(false)

            throw Error('Failed to get passkey authentication options')
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

            throw Error('Invalid passkey. Please try again.')
        }

        setLoading(false)
    }

    return { loading, confirm }
}

export default usePasskeyConfirmation
