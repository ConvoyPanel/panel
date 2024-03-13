import { rawDataToCoterm } from '@/api/admin/coterms/getCoterms'
import http from '@/api/http'


const updateAttachedNodes = async (id: number, nodeIds: number[]) => {
    const {
        data: { data },
    } = await http.put(`/api/admin/coterms/${id}/nodes`, {
        node_ids: nodeIds,
    })

    return rawDataToCoterm(data)
}

export default updateAttachedNodes