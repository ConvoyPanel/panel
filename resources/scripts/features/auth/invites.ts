import InviteController from '@/wayfinder/actions/App/Http/Controllers/Auth/InviteController'
import { queryOptions } from '@tanstack/react-query'

import { type DataResponse, apiFetch } from '@/lib/api'

export interface Invite {
    name: string
    email: string
    expiresAt: string
}

/**
 * Who the invite is for, so the screen can greet them by name before they have signed in.
 *
 * Nothing about the panel's contents comes back — an unauthenticated caller holding a guessed
 * token learns only what the email that carried it already told them.
 */
export const getInvite = async (token: string): Promise<Invite> =>
    (await apiFetch<DataResponse<Invite>>(InviteController.show(token))).data

export const inviteQuery = (token: string) =>
    queryOptions({
        queryKey: ['auth', 'invite', token] as const,
        queryFn: () => getInvite(token),
        // A dead link does not become alive again, and an unauthenticated screen should not sit
        // in a retry loop against a throttled endpoint.
        retry: false,
        staleTime: Infinity,
    })

export const acceptInvite = async (
    token: string,
    password: string
): Promise<void> => {
    await apiFetch(InviteController.store(token), {
        body: { password, password_confirmation: password },
    })
}
