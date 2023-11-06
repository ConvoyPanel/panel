import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes'
import http from '@/api/http'

const getNode = async (id: number): Promise<Node> => {
    const {
        data: { data },
    } = await http.get(`/api/admin/nodes/${id}`)

    return rawDataToNode(data)
}

export default getNode