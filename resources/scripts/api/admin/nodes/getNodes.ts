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
    memoryAllocated: number
    disk: number
    diskOverallocate: number
    diskAllocated: number
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
    memoryAllocated: data.memory_allocated,
    disk: data.disk,
    diskOverallocate: data.disk_overallocate,
    diskAllocated: data.disk_allocated,
    vmStorage: data.vm_storage,
    backupStorage: data.backup_storage,
    network: data.network,
    serversCount: data.servers_count,
})

export type NodeResponse = PaginatedResult<Node>

export interface QueryParams {
    query?: string
    page?: number
    perPage?: number
}


const getNodes = async ({
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

export default getNodes