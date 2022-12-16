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
    vmStorage: number
    backupStorage: number
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