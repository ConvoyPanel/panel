import useSWR from '@/lib/swr'

import getRecoveryCodes from '@/api/account/authenticator/getRecoveryCodes.ts'


const getKey = () => 'account.authenticator.recovery-codes'

const useRecoveryCodesSWR = () => {
    return useSWR(getKey(), getRecoveryCodes)
}

export default useRecoveryCodesSWR
