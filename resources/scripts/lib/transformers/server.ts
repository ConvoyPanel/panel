import { Server, ServerStateData, ServerTimepointData } from '@/types/server.ts'

import { rawDataToNode } from '@/lib/transformers/node'

export const rawDataToServer = (data: any): Server => ({
    id: data.id,
    uuid: data.uuid,
    uuidShort: data.uuidShort,
    nodeId: data.nodeId,
    networkInterfaceId: data.networkInterfaceId,
    node: data.node ? rawDataToNode(data.node.data ?? data.node) : undefined,
    userId: data.userId,
    vmid: data.vmid,
    hostname: data.hostname,
    name: data.name,
    description: data.description,
    lifecycle: data.lifecycle,
    suspendedAt: data.suspendedAt ?? null,
    powerState: data.powerState ?? null,
    cpu: data.cpu,
    memory: data.memory,
    disk: data.disk,
    backup: {
        countLimit: data.backupCountLimit,
        sizeLimit: data.backupSizeLimit,
        hasStorage: data.hasBackupStorage,
    },
    bandwidth: {
        usage: data.bandwidthUsage,
        limit: data.bandwidthLimit,
        speedLimit: data.speedLimit,
        overagePenalty: data.overagePenalty,
    },
    vlanTag: data.vlanTag,
    createdAt: new Date(data.createdAt),
})

export const rawDataToServerStateData = (data: any): ServerStateData => ({
    powerState: data.powerState,
    cpuUsed: data.cpuUsed,
    memoryTotal: data.memoryTotal,
    memoryUsed: data.memoryUsed,
    uptime: data.uptime,
    pendingPowerAction: data.pendingPowerAction ?? null,
    lastPowerAction: data.lastPowerAction ?? null,
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
