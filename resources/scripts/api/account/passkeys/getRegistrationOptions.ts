import { PublicKeyCredentialCreationOptionsJSON } from '@simplewebauthn/browser'

import axios from '@/lib/axios.ts'


const getRegistrationOptions =
    async (): Promise<PublicKeyCredentialCreationOptionsJSON> => {
        const { data } = await axios.get(
            '/api/client/account/passkeys/registration-options'
        )

        return data
    }

export default getRegistrationOptions
