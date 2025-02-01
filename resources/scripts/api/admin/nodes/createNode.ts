import { hostname } from '@/utils/validation.ts'
import { z } from 'zod'

export const nodeSchema = z.object({
    name: z.string().min(1).max(50),
    locationId: z.coerce.number(),
    fqdn: hostname().min(1).max(191),
    port: z.coerce.number().int().min(1).max(65535),
    cluster: z.string().min(1).max(191),
    verifyTls: z.boolean(),
    tokenId: z.string().min(1).max(191),
    secret: z.string().min(1).max(191),
})
