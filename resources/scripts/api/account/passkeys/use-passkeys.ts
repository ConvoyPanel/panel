import { useQuery } from '@tanstack/react-query'

import getPasskeys from '@/api/account/passkeys/getPasskeys.ts'

export const getKey = () => ['account.passkeys']

const usePasskeys = () => {
    return useQuery({ queryKey: getKey(), queryFn: getPasskeys })
}

export default usePasskeys
