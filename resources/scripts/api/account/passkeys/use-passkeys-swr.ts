import useSWR from '@/lib/swr'

import getPasskeys from '@/api/account/passkeys/getPasskeys.ts'

export const getKey = () => 'account.passkeys'

const usePasskeysSWR = () => {
    return useSWR(getKey(), getPasskeys)
}

export default usePasskeysSWR
