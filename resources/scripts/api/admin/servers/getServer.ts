import { rawDataToServerBuild, ServerBuild as BaseServerBuild } from '@/api/server/getServer'
import http from '@/api/http'
import { ServerInclude } from '@/api/admin/servers/getServers'

export interface AdminServerBuild extends BaseServerBuild {
    userId: number
    nodeId: number
    vmid: number
}

export const rawDataToAdminServer = (data: any): AdminServerBuild => ({
    ...rawDataToServerBuild(data),
    userId: data.user_id,
    nodeId: data.node_id,
    vmid: data.vmid,
})

export const getServer = async (uuid: string, include?: ServerInclude[]): Promise<AdminServerBuild> => {
    const {
        data: { data },
    } = await http.get(`/api/admin/servers/${uuid}`, {
        params: {
            include: include?.join(','),
        },
    })

    return rawDataToAdminServer(data)
}
