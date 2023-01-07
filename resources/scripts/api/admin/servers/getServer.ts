import { rawDataToServerObject, Server as BaseServer } from '@/api/server/getServer'
import http from '@/api/http'
import { ServerIncludes } from '@/api/admin/servers/getServers'

export interface Server extends BaseServer {
    userId: number
    nodeId: number
    vmid: number
}

export const rawDataToAdminServer = (data: any): Server => ({
    ...rawDataToServerObject(data),
    userId: data.user_id,
    nodeId: data.node_id,
    vmid: data.vmid,
})

export const getServer = async (uuid: string, includes?: ServerIncludes[]): Promise<Server> => {
    const {
        data: { data },
    } = await http.get(`/api/admin/servers/${uuid}`, {
        params: {
            includes: includes?.join(','),
        },
    })

    return rawDataToAdminServer(data)
}
