import http, { PaginatedResult, getPaginationSet } from '@/api/http'

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
    isoStorage: string
    network: string
    cotermEnabled: boolean
    cotermTlsEnabled: boolean
    cotermFqdn: string | null
    cotermPort: number
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
    isoStorage: data.iso_storage,
    network: data.network,
    cotermEnabled: data.coterm_enabled,
    cotermTlsEnabled: data.coterm_tls_enabled,
    cotermFqdn: data.coterm_fqdn,
    cotermPort: data.coterm_port,
    serversCount: data.servers_count,
})

export type NodeResponse = PaginatedResult<Node>

export interface QueryParams {
    query?: string | null
    cotermId?: number | null
    id?: number | number[] | string | string[]
    page?: number
    perPage?: number
}

const getNodes = async ({
    query,
    cotermId,
    id,
    perPage = 50,
    ...params
}: QueryParams): Promise<NodeResponse> => {
    const { data } = await http.get('/api/admin/nodes', {
        params: {
            'filter[*]': query,
            'filter[coterm_id]': cotermId === null ? '' : cotermId,
            'filter[id]': id
                ? Array.isArray(id)
                    ? id.join(',')
                    : id
                : undefined,
            ...params,
        },
    })

    return {
        items: (data.data || []).map((datum: any) => rawDataToNode(datum)),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getNodes