import http, { PaginatedResult, getPaginationSet } from '@/api/http'

export interface Coterm {
    id: number
    name: string
    isTlsEnabled: boolean
    fqdn: string
    port: number
    nodesCount: number
    tokenId?: string
    token?: string
}

export type CotermResponse = PaginatedResult<Coterm>

export interface QueryParams {
    query?: string
    page?: number
    perPage?: number
}

const getCoterms = async ({
    query,
    page,
    perPage = 50,
}: QueryParams): Promise<CotermResponse> => {
    const { data } = await http.get('/api/admin/coterms', {
        params: {
            'filter[*]': query,
            page,
            'per_page': perPage,
        },
    })

    return {
        items: data.data.map(rawDataToCoterm),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export const rawDataToCoterm = (data: any): Coterm => ({
    id: data.id,
    name: data.name,
    isTlsEnabled: Boolean(data.is_tls_enabled),
    fqdn: data.fqdn,
    port: data.port,
    nodesCount: data.nodes_count,
    tokenId: data.token_id,
    token: data.token,
})

export default getCoterms