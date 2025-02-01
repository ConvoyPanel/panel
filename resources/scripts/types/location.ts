import { PaginatedResult } from '@/utils/http.ts'
import { z } from 'zod'

export const locationSchema = z.object({
    shortCode: z.string().min(1).max(60),
    description: z.string().max(191).nullable(),
})

export interface Location {
    id: number
    shortCode: string
    description: string
    nodesCount: number
    serversCount: number
}

export type PaginatedLocations = PaginatedResult<Location>
