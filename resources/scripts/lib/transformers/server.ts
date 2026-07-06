import { rawDataToNode } from '@/lib/transformers/node'
import { Server, ServerStateData, ServerTimepointData } from '@/types/server.ts'

export const rawDataToServer = (data: any): Server => ({
    id: data.id,
    uuid: data.uuid,
    uuidShort: data.uuidShort,
    nodeId: data.nodeId,
    node: data.node ? rawDataToNode(data.node.data ?? data.node) : undefined,
    userId: data.userId,
    vmid: data.vmid,
    hostname: data.hostname,
    name: data.name,
    description: data.description,
    status: data.status,
    cpu: data.cpu,
    memory: data.memory,
    disk: data.disk,
    backup: {
        countLimit: data.backupCountLimit,
        sizeLimit: data.backupSizeLimit,
    },
    bandwidth: {
        usage: data.bandwidthUsage,
        limit: data.bandwidthLimit,
    },
    createdAt: new Date(data.createdAt),
})

export const rawDataToServerStateData = (data: any): ServerStateData => ({
    state: data.state,
    cpuUsed: data.cpuUsed,
    memoryTotal: data.memoryTotal,
    memoryUsed: data.memoryUsed,
    uptime: data.uptime,
})

export const rawDataToServerTimepointData = (
    data: any
): ServerTimepointData => ({
    cpuUsed: data.cpuUsed,
    memoryUsed: data.memoryUsed,
    network: {
        in: data.network.in,
        out: data.network.out,
    },
    disk: {
        write: data.disk.write,
        read: data.disk.read,
    },
    timestamp: new Date(data.timestamp),
})
