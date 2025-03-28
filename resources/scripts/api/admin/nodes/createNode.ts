import { hostname } from '@/utils/validation.ts'
import { z } from 'zod'

export const nodeSchema = z.object({
    displayName: z
        .string()
        .min(1, 'Display name is required')
        .max(50, "Display name can't exceed 50 characters"),
    locationId: z.coerce.number().positive('Location is required'),
    fqdn: hostname().min(1).max(191),
    port: z.coerce.number().int().min(1, 'Invalid').max(65535, 'Invalid'),
    name: z.string().min(1).max(191),
    verifyTls: z.boolean(),
    tokenId: z.string().min(1).max(191),
    secret: z.string().min(1).max(191),
})
