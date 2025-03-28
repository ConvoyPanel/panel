import { Node } from '@/types/node.ts'

export const rawDataToNode = (data: any): Node => ({
    id: data.id,
    locationId: data.location_id,
    displayName: data.display_name,
    name: data.name,
    verifyTls: data.verify_tls,
    fqdn: data.fqdn,
    port: data.port,
    memory: data.memory,
    memoryOverallocate: data.memory_overallocate,
    memoryAllocated: data.memory_allocated,
    cotermId: data.coterm_id,
    serversCount: data.servers_count,
})
