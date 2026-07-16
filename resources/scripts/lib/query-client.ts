import { QueryClient } from '@tanstack/react-query'
import { AxiosError } from 'axios'

/**
 * A 4xx is a verdict, not a blip: the same request repeated gets the same
 * answer, so React Query's default `retry: 3` just delays the truth. It cost
 * real debugging time once — the passkey list fetched behind
 * RequireIdentityConfirmation, 403'd, and then sat pending through three
 * backoffs (1s/2s/4s) while the user confirmed, so whichever retry happened to
 * land afterwards is what painted. A multi-second skeleton with no relation to
 * how long anything took, and an error state if they confirmed too slowly.
 *
 * Gating such a query on `enabled` is still the right fix (see
 * `useIdentityConfirmed`) — this just stops the failure mode being expensive
 * when something slips through.
 *
 * 408 and 429 are the exceptions: those genuinely are "try again", and the
 * default backoff is the right way to.
 */
const isRetryable = (error: unknown): boolean => {
    const status = (error as AxiosError)?.response?.status

    if (!status || status < 400 || status >= 500) return true

    return status === 408 || status === 429
}

export const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 30_000,
            refetchOnWindowFocus: false,
            retry: (failureCount, error) =>
                isRetryable(error) && failureCount < 3,
        },
    },
})
