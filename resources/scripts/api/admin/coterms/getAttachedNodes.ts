import { NodeResponse, rawDataToNode } from '@/api/admin/nodes/getNodes'
import http, { getPaginationSet } from '@/api/http'


export interface QueryParams {
    query?: string | null
    fqdn?: string | null
    locationId?: number | null
    page?: number | null
    perPage?: number | null
}

const getAttachedNodes = async (
    id: number,
    { query, fqdn, locationId, page, perPage = 50 }: QueryParams
): Promise<NodeResponse> => {
    const { data } = await http.get(`/api/admin/coterms/${id}/nodes`, {
        params: {
            query,
            fqdn,
            location_id: locationId,
            page,
            per_page: perPage,
        },
    })

    return {
        items: data.data.map(rawDataToNode),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getAttachedNodes