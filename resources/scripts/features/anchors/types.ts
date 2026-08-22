import type { PaginatedResult } from '@/utils/http.ts'
import { z } from 'zod'

/** A machine that has introduced itself and is waiting to be let in. */
export type AnchorEnrollmentQueueItem =
    App.Data.Anchor.AnchorEnrollmentQueueData
export type PaginatedEnrollments = PaginatedResult<AnchorEnrollmentQueueItem>

export type Relay = App.Data.Anchor.RelayData
export type PaginatedRelays = PaginatedResult<Relay>

/** The one-shot install command handed back when a token is issued. */
export type AnchorEnrollment = App.Data.Anchor.AnchorEnrollmentData
export type EnrollmentKey = App.Data.Anchor.AnchorEnrollmentKeyData
export type PaginatedEnrollmentKeys = PaginatedResult<EnrollmentKey>

export const relaySchema = z.object({
    name: z.string().min(1).max(191),
    publicUrl: z.url(),
    // Blank means the panel's own APP_URL, which is right for relays that can
    // resolve it.
    panelUrlOverride: z.union([z.url(), z.literal('')]),
})

/**
 * What an operator still answers when letting a machine in.
 *
 * Split deliberately: the first group is policy the host must not decide, the
 * second is how the panel reaches it, and the third is what the host already
 * reported and is only confirming.
 */
export const approveNodeSchema = z.object({
    locationId: z.coerce.number().positive('Location is required'),
    memoryOverallocate: z.coerce.number().int().min(0),
    relayId: z.string().default('none'),

    fqdn: z.string().min(1).max(191),
    port: z.coerce.number().int().min(1).max(65535),
    agentPublicUrl: z.url('Enter the address the panel reaches this agent on'),

    tokenId: z.string().min(1, 'Required').max(191),
    tokenSecret: z.string().min(1, 'Required').max(191),

    displayName: z.string().min(1).max(50),
    name: z.string().min(1).max(191),
    socketCount: z.coerce.number().int().min(1),
    coreCount: z.coerce.number().int().min(1),
    cpuCount: z.coerce.number().int().min(1),
    memory: z.coerce.number().int().min(1),
})

export const approveRelaySchema = z.object({
    name: z.string().min(1).max(191),
    publicUrl: z.url(),
})

/** How long a key lives. `null` is deliberately reachable only on purpose. */
export const enrollmentKeySchema = z.object({
    name: z.string().min(1).max(191),
    mode: z.enum(['any', 'agent', 'relay']),
    maxUses: z.string(),
    expiresInMinutes: z.string(),
})
