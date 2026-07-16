import RecoveryCodeController from '@/wayfinder/actions/App/Http/Controllers/Client/RecoveryCodeController'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { apiFetch } from '@/lib/api'

/**
 * Recovery codes belong to the account, not to the authenticator. One set backs
 * every second factor: the first one enabled mints them, they survive swapping
 * one factor for another, and removing the last one clears them. They used to be
 * fetched from `authenticator/api.ts` by the passkey dialog too, which is how the
 * UI ended up presenting one set as if it were two.
 */
export const hasRecoveryCodes = async (): Promise<boolean> => {
    const data = await apiFetch<{ enabled: boolean }>(
        RecoveryCodeController.status()
    )

    return data.enabled
}

export const getRecoveryCodes = (): Promise<string[]> =>
    apiFetch<string[]>(RecoveryCodeController.index())

export const regenerateRecoveryCodes = async (): Promise<void> => {
    await apiFetch(RecoveryCodeController.store())
}

export const recoveryCodeQueries = {
    all: () => ['account', 'recovery-codes'] as const,
    status: () =>
        queryOptions({
            queryKey: [...recoveryCodeQueries.all(), 'status'] as const,
            queryFn: hasRecoveryCodes,
        }),
    codes: () =>
        queryOptions({
            queryKey: [...recoveryCodeQueries.all(), 'codes'] as const,
            queryFn: getRecoveryCodes,
        }),
}

export const useHasRecoveryCodes = () => useQuery(recoveryCodeQueries.status())
