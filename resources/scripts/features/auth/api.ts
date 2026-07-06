import type {
    AuthenticationResponseJSON,
    PublicKeyCredentialRequestOptionsJSON,
} from '@simplewebauthn/browser'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { rawDataToAuthenticatedUser } from '@/api/transformers/user.ts'
import { apiFetch, type DataResponse } from '@/lib/api'
import { queryClient } from '@/lib/query-client.ts'
import AuthenticatedSessionController from '@/wayfinder/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController'
import PasskeyLoginController from '@/wayfinder/actions/App/Http/Controllers/Auth/PasskeyLoginController'
import SessionController from '@/wayfinder/actions/App/Http/Controllers/Client/SessionController'
import TwoFactorAuthenticatedSessionController from '@/wayfinder/actions/Laravel/Fortify/Http/Controllers/TwoFactorAuthenticatedSessionController'

// The Fortify session controllers also expose the stock `/login`, `/logout`, and
// `/two-factor-challenge` routes, so Wayfinder emits URI-keyed dictionaries —
// reference the `/api/auth/*` keys explicitly.
const loginRoute = AuthenticatedSessionController.store['/api/auth/login']
const logoutRoute = AuthenticatedSessionController.destroy['/api/auth/logout']
const verifyChallengeRoute =
    TwoFactorAuthenticatedSessionController.store[
        '/api/auth/authenticator/verify-challenge'
    ]

interface LoginParams {
    email: string
    password: string
}

interface LoginResponse {
    twoFactor: boolean
}

export const getUser = async () => {
    const { data } = await apiFetch<DataResponse<any>>(SessionController())

    return rawDataToAuthenticatedUser(data)
}

export const currentUserQueries = {
    all: () => ['user'] as const,
    detail: () =>
        queryOptions({
            queryKey: currentUserQueries.all(),
            queryFn: () => getUser(),
        }),
}

export const cacheUser = async () => {
    const user = await getUser()

    queryClient.setQueryData(currentUserQueries.all(), user)
}

export const useUser = () => useQuery(currentUserQueries.detail())

export const login = async ({
    email,
    password,
}: LoginParams): Promise<LoginResponse> => {
    const data = await apiFetch<{ two_factor?: boolean }>(loginRoute(), {
        body: { email, password },
    })

    return {
        twoFactor: Boolean(data?.two_factor),
    } satisfies LoginResponse
}

export const logout = async (): Promise<void> => {
    await apiFetch(logoutRoute())
}

export const getPasskeyAuthenticationOptions =
    (): Promise<PublicKeyCredentialRequestOptionsJSON> =>
        apiFetch<PublicKeyCredentialRequestOptionsJSON>(
            PasskeyLoginController.create()
        )

export const verifyPasskeyAuthentication = async (
    authResponse: AuthenticationResponseJSON
): Promise<void> => {
    await apiFetch(PasskeyLoginController.store(), {
        body: { ...authResponse },
    })
}

export const verifyAuthenticatorChallenge = async ({
    code,
    recoveryCode,
}: {
    code?: string | null
    recoveryCode?: string | null
}): Promise<void> => {
    await apiFetch(verifyChallengeRoute(), {
        body: { code, recovery_code: recoveryCode },
    })
}
