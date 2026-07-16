import type {
    AuthenticationResponseJSON,
    PublicKeyCredentialRequestOptionsJSON,
} from '@simplewebauthn/browser'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { apiFetch } from '@/lib/api'
import ConfirmableIdentityController from '@/wayfinder/actions/App/Http/Controllers/Auth/ConfirmableIdentityController'

interface Payload {
    passkey?: AuthenticationResponseJSON | null
    password?: string | null
}

interface RawIdentityStatus {
    confirmed: boolean
    expires_in: number | null
}

export interface IdentityStatus {
    confirmed: boolean
    /** Seconds left on the confirmation, or null when there is none. */
    expiresIn: number | null
}

const toIdentityStatus = (raw: RawIdentityStatus): IdentityStatus => ({
    confirmed: raw.confirmed,
    expiresIn: raw.expires_in,
})

/**
 * Whether this session's identity is confirmed, according to the server.
 *
 * The client used to answer this itself out of a persisted zustand store — two
 * clocks for one fact. The persisted copy kept only the *method* used, not the
 * timestamp, so a reload re-raised the gate even though the server still
 * trusted the session for the rest of the window; and nothing made it notice
 * the window lapsing. RequireIdentityConfirmation is what actually enforces
 * this, so it is what gets asked.
 */
export const getIdentityStatus = async (): Promise<IdentityStatus> =>
    toIdentityStatus(
        await apiFetch<RawIdentityStatus>(ConfirmableIdentityController.show())
    )

export const identityQueries = {
    all: () => ['auth', 'identity'] as const,
    status: () =>
        queryOptions({
            queryKey: identityQueries.all(),
            queryFn: getIdentityStatus,
            // A fact about the session rather than a cached read.
            staleTime: 0,
            // One well-timed refetch rather than a poll: scheduled for the
            // moment the window lapses, at which point the answer becomes
            // `confirmed: false` with no expiry and this stops on its own.
            refetchInterval: query =>
                query.state.data?.expiresIn
                    ? query.state.data.expiresIn * 1000
                    : false,
        }),
}

/**
 * True only once the server has confirmed identity.
 *
 * Reach for this as the `enabled` of any query behind
 * RequireIdentityConfirmation: it stays false while the status is unknown, so
 * nothing fires early and 403s.
 */
export const useIdentityConfirmed = (): boolean =>
    useQuery(identityQueries.status()).data?.confirmed === true

/**
 * True only once the server has said identity is NOT confirmed.
 *
 * Deliberately not `!useIdentityConfirmed()` — that is also true while the
 * first request is still in flight, which would flash the gate open on every
 * mount before the answer arrives.
 */
export const useIdentityUnconfirmed = (): boolean =>
    useQuery(identityQueries.status()).data?.confirmed === false

/** Returns the fresh status, so the caller can seed the cache without a refetch. */
export const confirmIdentity = async ({
    passkey,
    password,
}: Payload): Promise<IdentityStatus> =>
    toIdentityStatus(
        await apiFetch<RawIdentityStatus>(ConfirmableIdentityController.store(), {
            body: {
                passkey: passkey ? JSON.stringify(passkey) : undefined,
                password,
            },
        })
    )

export const getPasskeyAuthenticationOptions =
    (): Promise<PublicKeyCredentialRequestOptionsJSON> =>
        apiFetch<PublicKeyCredentialRequestOptionsJSON>(
            ConfirmableIdentityController.generatePasskeyAuthOptions()
        )
