import { addDays, addYears, formatDistanceToNow, isPast } from 'date-fns'
import { z } from 'zod'

/** The lifetimes offered when minting a token, application or personal. */
export const tokenExpiryOptions = [
    { value: 'never', label: 'Never' },
    { value: '30d', label: '30 days' },
    { value: '90d', label: '90 days' },
    { value: '1y', label: '1 year' },
] as const

export type TokenExpiry = (typeof tokenExpiryOptions)[number]['value']

export const tokenExpirySchema = z.enum(['never', '30d', '90d', '1y'])

/**
 * The `expires_at` field for a create request, counted from now. Empty for a
 * token that never expires, since the API reads an omitted field as "never".
 */
export const expiresAtPayload = (
    expiry: TokenExpiry
): { expires_at?: string } => {
    const now = new Date()

    switch (expiry) {
        case '30d':
            return { expires_at: addDays(now, 30).toISOString() }
        case '90d':
            return { expires_at: addDays(now, 90).toISOString() }
        case '1y':
            return { expires_at: addYears(now, 1).toISOString() }
        default:
            return {}
    }
}

export const isTokenExpired = (expiresAt: string | Date | null) =>
    expiresAt !== null && isPast(new Date(expiresAt))

/** `in 3 months`, `Never` or `Expired`, worded like the lists' last-used column. */
export const formatTokenExpiry = (expiresAt: string | Date | null): string => {
    if (expiresAt === null) return 'Never'
    if (isTokenExpired(expiresAt)) return 'Expired'

    return formatDistanceToNow(new Date(expiresAt), { addSuffix: true })
}

/** The same, as a phrase that stands on its own in a row's meta line. */
export const describeTokenExpiry = (
    expiresAt: string | Date | null
): string => {
    if (expiresAt === null) return 'Never expires'
    if (isTokenExpired(expiresAt)) return 'Expired'

    return `Expires ${formatTokenExpiry(expiresAt)}`
}
