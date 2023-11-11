import { rawDataToAdminServer } from '@/api/admin/servers/getServer'
import http from '@/api/http'
import { EloquentStatus } from '@/api/server/types'

interface UpdateServerParameters {
    name?: string | null
    hostname?: string | null
    vmid?: number | null
    userId?: number | null
    status?: EloquentStatus
}

const updateServer = async (
    serverUuid: string,
    { userId, ...params }: UpdateServerParameters
) => {
    const {
        data: { data },
    } = await http.patch(`/api/admin/servers/${serverUuid}`, {
        user_id: userId,
        ...params,
    })

    return rawDataToAdminServer(data)
}

export default updateServer
