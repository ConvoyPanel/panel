import { AuthenticationResponseJSON } from '@simplewebauthn/browser'

import axios from '@/lib/axios.ts'


const verifyPasskeyAuthentication = async (
    authResponse: AuthenticationResponseJSON
) => {
    await axios.post('/api/auth/passkeys/verify-authentication', {
        ...authResponse,
    })
}

export default verifyPasskeyAuthentication
