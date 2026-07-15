import { z } from 'zod'

/**
 * Shared shape for the overage-penalty override control (segmented
 * Inherit | Custom). The cascade is server -> node -> global, so any tier that
 * can be left on "inherit" reuses these fields; see
 * docs/bandwidth-rate-limiting-plan.md §5.
 */

/** Proxmox rate limits are decimal MB/s, not MiB/s — see RateLimitCast. */
export const BYTES_PER_MB = 1_000_000

/** Proxmox's floor for a rate limit is 1 MB/s. */
export const MIN_RATE_MB = 1

export type OveragePenalty = App.Data.Server.OveragePenaltyData

export const overagePenaltyFields = {
    overagePenaltyMode: z.enum(['inherit', 'custom']),
    overagePenaltyAction: z.enum(['throttle', 'disconnect']),
    // Kept as a string so an emptied input doesn't coerce to 0 and trip the
    // min() before the user finishes typing; validated in the refinement below.
    overagePenaltyRate: z.string().optional(),
}

/**
 * A rate is only required when the user has actually chosen a custom throttle;
 * `disconnect` has no rate, and `inherit` sends nothing at all.
 */
export const refineOveragePenalty = <T extends z.ZodTypeAny>(schema: T) =>
    schema.superRefine((data: any, ctx: z.RefinementCtx) => {
        if (data.overagePenaltyMode !== 'custom') return
        if (data.overagePenaltyAction !== 'throttle') return

        const raw = data.overagePenaltyRate
        const n = Number(raw)

        if (raw === '' || raw == null || Number.isNaN(n)) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ['overagePenaltyRate'],
                message: 'A throttle rate is required',
            })
            return
        }

        if (n < MIN_RATE_MB) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ['overagePenaltyRate'],
                message: `Must be at least ${MIN_RATE_MB} MB/s`,
            })
        }
    })

/** Form defaults for a tier whose stored override may be null (= inherit). */
export const overagePenaltyDefaults = (penalty: OveragePenalty | null) => ({
    overagePenaltyMode: (penalty ? 'custom' : 'inherit') as 'inherit' | 'custom',
    overagePenaltyAction: (penalty?.action ?? 'throttle') as
        | 'throttle'
        | 'disconnect',
    overagePenaltyRate:
        penalty?.rate != null ? String(penalty.rate / BYTES_PER_MB) : '',
})

/**
 * Form values -> the API's `overage_penalty` payload. `null` clears the override
 * (back to inherit); the backend treats a null as "inherit up the cascade".
 */
export const overagePenaltyPayload = (values: {
    overagePenaltyMode: 'inherit' | 'custom'
    overagePenaltyAction: 'throttle' | 'disconnect'
    overagePenaltyRate?: string
}): { action: string; rate: number | null } | null => {
    if (values.overagePenaltyMode === 'inherit') return null

    if (values.overagePenaltyAction === 'disconnect') {
        return { action: 'disconnect', rate: null }
    }

    return {
        action: 'throttle',
        rate: Math.round(Number(values.overagePenaltyRate) * BYTES_PER_MB),
    }
}

/** Human summary of a resolved penalty, e.g. "Throttle to 10 MB/s". */
export const describePenalty = (penalty: OveragePenalty | null): string => {
    if (!penalty) return 'Not configured'
    if (penalty.action === 'disconnect') return 'Disconnect the NIC'
    if (penalty.rate == null) return 'Throttle'

    return `Throttle to ${penalty.rate / BYTES_PER_MB} MB/s`
}
