import { useQuery } from '@tanstack/react-query'

import getSecretKey from '@/api/account/authenticator/getSecretKey.ts'


export const getKey = () => ['account.authenticator.secret-key']

const useSecretKey = () => {
    return useQuery({ queryKey: getKey(), queryFn: getSecretKey })
}

export default useSecretKey
