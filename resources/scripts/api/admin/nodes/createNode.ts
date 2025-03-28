import { hostname } from '@/utils/validation.ts'
import { z } from 'zod'

export const nodeSchema = z.object({
    name: z
        .string()
        .min(1, 'Name is required')
        .max(50, "Name can't exceed 50 characters"),
    locationId: z.coerce.number().positive('Location is required'),
    fqdn: hostname().min(1).max(191),
    port: z.coerce.number().int().min(1, 'Invalid').max(65535, 'Invalid'),
    cluster: z.string().min(1).max(191),
    verifyTls: z.boolean(),
    tokenId: z.string().min(1).max(191),
    secret: z.string().min(1).max(191),
})
