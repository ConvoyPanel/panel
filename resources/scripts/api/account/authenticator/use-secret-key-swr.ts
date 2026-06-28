import useSWR from '@/lib/swr'

import getSecretKey from '@/api/account/authenticator/getSecretKey.ts'


const getKey = () => 'account.authenticator.secret-key'

const useSecretKeySWR = () => {
    return useSWR(getKey(), getSecretKey)
}

export default useSecretKeySWR
