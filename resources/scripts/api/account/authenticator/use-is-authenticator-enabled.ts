import { useQuery } from '@tanstack/react-query'

import isAuthenticatorEnabled from '@/api/account/authenticator/isAuthenticatorEnabled.ts'

export const getKey = () => ['account.authenticator.enabled']

const useIsAuthenticatorEnabled = () => {
    return useQuery({ queryKey: getKey(), queryFn: isAuthenticatorEnabled })
}

export default useIsAuthenticatorEnabled
