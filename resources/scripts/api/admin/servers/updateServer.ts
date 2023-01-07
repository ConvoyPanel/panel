import { EloquentStatus } from '@/api/server/types'
import http from '@/api/http'
import { rawDataToAdminServer } from '@/api/admin/servers/getServer'

interface UpdateServerParameters {
    name: string
    hostname: string
    vmid: number
    userId: number
    nodeId: number
    status: EloquentStatus
}

const updateServer = async (serverUuid: string, { userId, nodeId, ...params }: UpdateServerParameters) => {
    const {
        data: { data },
    } = await http.patch(`/api/admin/servers/${serverUuid}`, {
        node_id: nodeId,
        user_id: userId,
        ...params,
    })

    return rawDataToAdminServer(data)
}

export default updateServer
