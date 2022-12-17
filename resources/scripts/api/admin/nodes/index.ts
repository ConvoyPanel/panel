import http, { getPaginationSet, PaginatedResult } from '@/api/http'

export interface Node {
    id: number
    locationId: number
    name: string
    cluster: string
    fqdn: string
    port: number
    memory: number
    memoryOverallocate: number
    disk: number
    diskOverallocate: number
    vmStorage: string
    backupStorage: string
    network: string
    serversCount: number
}

export const rawDataToNode = (data: any): Node => ({
    id: data.id,
    locationId: data.location_id,
    name: data.name,
    cluster: data.cluster,
    fqdn: data.fqdn,
    port: data.port,
    memory: data.memory,
    memoryOverallocate: data.memory_overallocate,
    disk: data.disk,
    diskOverallocate: data.disk_overallocate,
    vmStorage: data.vm_storage,
    backupStorage: data.backup_storage,
    network: data.network,
    serversCount: data.servers_count,
})

export interface QueryParams {
    query?: string
    page?: number
    perPage?: number
}

export type NodeResponse = PaginatedResult<Node>

export const getNodes = async ({
    query,
    perPage = 50,
    ...params
}: QueryParams): Promise<NodeResponse> => {
    const { data } = await http.get('/api/admin/nodes', {
        params: {
            'filter[name]': query,
            per_page: perPage,
            ...params,
        },
    })

    return {
        items: (data.data || []).map((datum: any) =>
            rawDataToNode(datum),
        ),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

interface CreateNodeParameters extends Omit<Node, 'id' | 'serversCount'> {
    tokenId: string
    secret: string
}

export const createNode = async (data: CreateNodeParameters): Promise<Node> => {
    const { data: { data: responseData } } = await http.post('/api/admin/nodes', {
        location_id: data.locationId,
        name: data.name,
        cluster: data.cluster,
        fqdn: data.fqdn,
        token_id: data.tokenId,
        secret: data.secret,
        port: data.port,
        memory: data.memory,
        memory_overallocate: data.memoryOverallocate,
        disk: data.disk,
        disk_overallocate: data.diskOverallocate,
        vm_storage: data.vmStorage,
        backup_storage: data.backupStorage,
        network: data.network,
    })

    return rawDataToNode(responseData)
}