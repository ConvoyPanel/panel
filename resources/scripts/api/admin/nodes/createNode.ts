import { hostname } from '@/utils/validation.ts'
import { z } from 'zod'

import axios from '@/lib/axios.ts'
import type { Node } from '@/types/node.ts'

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
    tokenSecret: z.string().min(1).max(191),
    rootPrivileges: z.literal(true),
    privilegeSeparationDisabled: z.literal(true),
    socketCount: z.coerce.number().int().min(1, 'Invalid'),
    coreCount: z.coerce.number().int().min(1, 'Invalid'),
    cpuCount: z.coerce.number().int().min(1, 'Invalid'),
    memory: z.coerce.number().int().min(1, 'Invalid'),
    memoryOverallocate: z.coerce.number().int().min(0, 'Invalid'),
})

const createNode = async (payload: z.infer<typeof nodeSchema>): Promise<Node> => {
    const {
        displayName,
        locationId,
        fqdn,
        port,
        name,
        verifyTls,
        tokenId,
        tokenSecret,
        socketCount,
        coreCount,
        cpuCount,
        memory,
        memoryOverallocate,
    } = payload

    const { data: { data } } = await axios.post(`/api/admin/nodes`, {
        display_name: displayName,
        location_id: locationId,
        fqdn,
        port,
        name,
        verify_tls: verifyTls,
        token_id: tokenId,
        token_secret: tokenSecret,
        socket_count: socketCount,
        core_count: coreCount,
        cpu_count: cpuCount,
        memory,
        memory_overallocate: memoryOverallocate,
    })

    return data as Node
}

export default createNode
