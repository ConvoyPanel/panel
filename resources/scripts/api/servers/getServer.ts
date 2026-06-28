import axios from '@/lib/axios.ts'
import type { Server } from '@/types/server.ts'

const getServer = async (uuid: string): Promise<Server> => {
    const { data: { data } } = await axios.get(`/api/client/servers/${uuid}`)
    return {
        id: data.id,
        uuid: data.uuid,
        uuidShort: data.uuidShort,
        nodeId: data.nodeId,
        node: undefined,
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
    }
}

export default getServer
