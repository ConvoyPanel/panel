import { RegistrationResponseJSON } from '@simplewebauthn/browser'

import axios from '@/lib/axios.ts'

import { rawDataToPasskey } from '@/api/transformers/passkey.ts'


const verifyRegistration = async (passkey: RegistrationResponseJSON) => {
    const {
        data: { data },
    } = await axios.post('/api/client/account/passkeys/verify-registration', {
        ...passkey,
    })

    return rawDataToPasskey(data)
}

export default verifyRegistration
