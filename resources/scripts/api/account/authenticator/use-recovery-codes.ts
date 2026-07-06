import { useQuery } from '@tanstack/react-query'

import getRecoveryCodes from '@/api/account/authenticator/getRecoveryCodes.ts'


export const getKey = () => ['account.authenticator.recovery-codes']

const useRecoveryCodes = () => {
    return useQuery({ queryKey: getKey(), queryFn: getRecoveryCodes })
}

export default useRecoveryCodes
