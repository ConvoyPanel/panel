import http from '@/api/http'
import { rawDataToServerObject } from '@/api/server/getServer'

interface ServerLimits {
    cpu: number
    memory: number
    disk: number
    snapshots: number | null
    backups: number | null
    bandwidth: number | null
    addressIds: number[]
}

interface CreateServerParameters {
    name: string
    userId: number
    nodeId: number
    vmid: number | null
    hostname: string
    limits: ServerLimits
    accountPassword: string
    shouldCreateServer: boolean
    templateUuid: string | null
    startAfterCompletion: boolean
}

const createServer = async ({
    nodeId,
    userId,
    accountPassword,
    shouldCreateServer,
    templateUuid,
    startAfterCompletion,
    limits: { addressIds, ...limits },
    ...params
}: CreateServerParameters) => {
    const {
        data: { data: responseData },
    } = await http.post('/api/admin/servers', {
        node_id: nodeId,
        user_id: userId,
        ...params,
        limits: {
            ...limits,
            address_ids: addressIds,
        },
        account_password: accountPassword,
        should_create_server: shouldCreateServer,
        ...(shouldCreateServer && {
            template_uuid: templateUuid,
        }),
        start_on_completion: startAfterCompletion,
    })

    return rawDataToServerObject(responseData)
}

export default createServer
