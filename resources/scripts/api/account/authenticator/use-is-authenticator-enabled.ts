import { queryOptions, useQuery } from '@tanstack/react-query'

import getQrCode from '@/api/account/authenticator/getQrCode.ts'
import getRecoveryCodes from '@/api/account/authenticator/getRecoveryCodes.ts'
import getSecretKey from '@/api/account/authenticator/getSecretKey.ts'
import isAuthenticatorEnabled from '@/api/account/authenticator/isAuthenticatorEnabled.ts'

export const authenticatorQueries = {
    all: () => ['account', 'authenticator'] as const,
    enabled: () =>
        queryOptions({
            queryKey: [...authenticatorQueries.all(), 'enabled'] as const,
            queryFn: isAuthenticatorEnabled,
        }),
    secretKey: () =>
        queryOptions({
            queryKey: [...authenticatorQueries.all(), 'secret-key'] as const,
            queryFn: getSecretKey,
        }),
    qrCode: () =>
        queryOptions({
            queryKey: [...authenticatorQueries.all(), 'qr-code'] as const,
            queryFn: getQrCode,
        }),
    recoveryCodes: () =>
        queryOptions({
            queryKey: [
                ...authenticatorQueries.all(),
                'recovery-codes',
            ] as const,
            queryFn: getRecoveryCodes,
        }),
}

const useIsAuthenticatorEnabled = () =>
    useQuery(authenticatorQueries.enabled())

export default useIsAuthenticatorEnabled
