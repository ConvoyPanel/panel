import http, {
    FractalResponseData,
    PaginatedResult,
    getPaginationSet,
} from '@/api/http'

export interface Location {
    id: number
    shortCode: string
    description: string | null
    nodesCount: number
    serversCount: number
}

export const rawDataToLocation = (data: FractalResponseData): Location => ({
    id: data.id,
    shortCode: data.short_code,
    description: data.description,
    nodesCount: data.nodes_count,
    serversCount: data.servers_count,
})

export interface QueryParams {
    query?: string
    page?: number
    perPage?: number
}

export type LocationResponse = PaginatedResult<Location>

export default ({
    query,
    perPage = 50,
    ...params
}: QueryParams): Promise<PaginatedResult<Location>> => {
    return new Promise((resolve, reject) => {
        http.get('/api/admin/locations', {
            params: {
                'filter[*]': query,
                'per_page': perPage,
                ...params,
            },
        })
            .then(({ data }) =>
                resolve({
                    items: (data.data || []).map((datum: any) =>
                        rawDataToLocation(datum)
                    ),
                    pagination: getPaginationSet(data.meta.pagination),
                })
            )
            .catch(reject)
    })
}