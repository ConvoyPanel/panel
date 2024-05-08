import { Server } from '@/types/server.ts'

export const rawDataToServer = (data: any): Server => ({
    id: data.id,
    uuid: data.uuid,
    uuidShort: data.uuid_short,
    nodeId: data.nodeId,
    userId: data.userId,
    vmid: data.vmid,
    hostname: data.hostname,
    name: data.name,
    description: data.description,
    status: data.status,
    cpu: data.cpu,
    memory: data.memory,
    disk: data.disk,
    bandwidthUsage: data.bandwidth_usage,
    bandwidthLimit: data.bandwidth_limit,
})
