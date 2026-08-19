import type { PaginatedResult } from '@/utils/http.ts'
import { z } from 'zod'

export type Anchor = App.Data.Anchor.AnchorData
export type AnchorEnrollment = App.Data.Anchor.AnchorEnrollmentData
export type PaginatedAnchors = PaginatedResult<Anchor>

export const anchorSchema = z.object({
    name: z.string().min(1).max(191),
    mode: z.enum(['agent', 'relay']),
    publicUrl: z.url(),
    // Blank means the panel's own APP_URL, which is right for anchors
    // that can resolve it.
    panelUrlOverride: z.union([z.url(), z.literal('')]),
    // A relay cannot route through another relay, which the form expresses by
    // not rendering the field at all for one. There is deliberately no refine
    // for it: the rule can now only be broken by a value nobody can see or
    // change, so it would fail submission with an error attached to nothing.
    // `payload()` drops it, and the server enforces it either way.
    relayId: z.string(),
})
