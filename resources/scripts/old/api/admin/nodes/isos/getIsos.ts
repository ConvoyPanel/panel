import http, { PaginatedResult, getPaginationSet } from '@/api/http'

export interface ISO {
    uuid: string
    isSuccessful: boolean
    name: string
    fileName: string
    size: number
    hidden: boolean
    completedAt?: Date
    createdAt: Date
}

export const rawDataToISO = (rawData: any): ISO => ({
    uuid: rawData.uuid,
    isSuccessful: rawData.is_successful,
    name: rawData.name,
    fileName: rawData.file_name,
    size: rawData.size,
    hidden: Boolean(rawData.hidden),
    completedAt: rawData.completed_at
        ? new Date(rawData.completed_at)
        : undefined,
    createdAt: new Date(rawData.created_at),
})

export interface QueryParams {
    nodeId: number
    query?: string
    page?: number
    perPage?: number
}

export type IsoResponse = PaginatedResult<ISO>

const getIsos = async ({
    nodeId,
    query,
    perPage = 50,
    ...params
}: QueryParams): Promise<IsoResponse> => {
    const { data } = await http.get(`/api/admin/nodes/${nodeId}/isos`, {
        params: {
            'filter[name]': query,
            'per_page': perPage,
            ...params,
        },
    })

    return {
        items: data.data.map(rawDataToISO),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getIsos