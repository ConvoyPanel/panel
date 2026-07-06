import { queryOptions, useQuery } from '@tanstack/react-query'

import { apiFetch } from '@/lib/api'
import AuthenticatorStatusController from '@/wayfinder/actions/App/Http/Controllers/Client/AuthenticatorStatusController'
import RecoveryCodeController from '@/wayfinder/actions/Laravel/Fortify/Http/Controllers/RecoveryCodeController'
import TwoFactorAuthenticationController from '@/wayfinder/actions/Laravel/Fortify/Http/Controllers/TwoFactorAuthenticationController'
import TwoFactorQrCodeController from '@/wayfinder/actions/Laravel/Fortify/Http/Controllers/TwoFactorQrCodeController'
import TwoFactorSecretKeyController from '@/wayfinder/actions/Laravel/Fortify/Http/Controllers/TwoFactorSecretKeyController'

export interface AuthenticatorQrCode {
    svg: string
    url: string
}

// These endpoints are served by Fortify controllers that also expose the
// stock `/user/*` routes, so Wayfinder emits URI-keyed dictionaries — reference
// the `/api/client/account/authenticator/*` route explicitly.
const qrCodeRoute =
    TwoFactorQrCodeController.show[
        '/api/client/account/authenticator/qr-code'
    ]
const secretKeyRoute =
    TwoFactorSecretKeyController.show[
        '/api/client/account/authenticator/secret-key'
    ]
const recoveryCodesRoute =
    RecoveryCodeController.index[
        '/api/client/account/authenticator/recovery-codes'
    ]
const regenerateRecoveryCodesRoute =
    RecoveryCodeController.store[
        '/api/client/account/authenticator/recovery-codes/regenerate'
    ]
const enableRoute =
    TwoFactorAuthenticationController.store[
        '/api/client/account/authenticator/enable'
    ]
const disableRoute =
    TwoFactorAuthenticationController.destroy[
        '/api/client/account/authenticator/disable'
    ]

export const isAuthenticatorEnabled = async (): Promise<boolean> => {
    const data = await apiFetch<{ enabled: boolean }>(
        AuthenticatorStatusController()
    )

    return data.enabled
}

export const getSecretKey = async (): Promise<string> => {
    const data = await apiFetch<{ secretKey: string }>(secretKeyRoute())

    return data.secretKey
}

export const getQrCode = (): Promise<AuthenticatorQrCode> =>
    apiFetch<AuthenticatorQrCode>(qrCodeRoute())

export const getRecoveryCodes = (): Promise<string[]> =>
    apiFetch<string[]>(recoveryCodesRoute())

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

export const useIsAuthenticatorEnabled = () =>
    useQuery(authenticatorQueries.enabled())

export const useQrCode = () => useQuery(authenticatorQueries.qrCode())

export const useSecretKey = () => useQuery(authenticatorQueries.secretKey())

export const useRecoveryCodes = () =>
    useQuery(authenticatorQueries.recoveryCodes())

export const enableAuthenticator = async (force?: boolean): Promise<void> => {
    await apiFetch(enableRoute(), { body: { force } })
}

export const disableAuthenticator = async (): Promise<void> => {
    await apiFetch(disableRoute())
}

export const regenerateRecoveryCodes = async (): Promise<void> => {
    await apiFetch(regenerateRecoveryCodesRoute())
}
