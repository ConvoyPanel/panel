import type { PaginatedResult } from '@/utils/http.ts'
import { z } from 'zod'

export type Anchor = App.Data.Anchor.AnchorData
export type AnchorEnrollment = App.Data.Anchor.AnchorEnrollmentData
export type PaginatedAnchors = PaginatedResult<Anchor>

export const anchorSchema = z
    .object({
        name: z.string().min(1).max(191),
        mode: z.enum(['agent', 'relay']),
        publicUrl: z.url(),
        relayId: z.string(),
    })
    .refine(data => data.mode === 'agent' || data.relayId === 'none', {
        path: ['relayId'],
        message: 'A relay cannot route through another relay',
    })
