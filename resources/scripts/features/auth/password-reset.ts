import PasswordResetController from '@/wayfinder/actions/App/Http/Controllers/Auth/PasswordResetController'
import { queryOptions } from '@tanstack/react-query'

import { apiFetch } from '@/lib/api'

/**
 * Whether the sign-in page offers "Forgot password?". False when an admin has
 * not turned resets on, or when the panel has no mail relay to send them with.
 */
export const passwordResetsEnabled = (): boolean =>
    window.SiteConfiguration?.passwordResets ?? false

/**
 * Resolves the same way whether or not the address has an account, so there is
 * nothing in the response to tell the two apart.
 */
export const requestPasswordReset = async (email: string): Promise<void> => {
    await apiFetch(PasswordResetController.store(), { body: { email } })
}

const checkResetLink = async (token: string, email: string): Promise<true> => {
    await apiFetch(PasswordResetController.show(token), { params: { email } })

    return true
}

export const resetLinkQuery = (token: string, email: string) =>
    queryOptions({
        queryKey: ['auth', 'password-reset', token, email] as const,
        queryFn: () => checkResetLink(token, email),
        // A dead link does not come back to life, and this is a throttled,
        // unauthenticated endpoint.
        retry: false,
        staleTime: Infinity,
    })

export const resetPassword = async (
    token: string,
    email: string,
    password: string
): Promise<void> => {
    await apiFetch(PasswordResetController.update(token), {
        body: { email, password, password_confirmation: password },
    })
}
