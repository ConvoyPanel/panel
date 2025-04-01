import axios from '@/lib/axios.ts'

import { rawDataToNode } from '@/api/transformers/node.ts'

const getNode = async (id: number) => {
    const {
        data: { data },
    } = await axios.get(`/api/admin/nodes/${id}`)

    return rawDataToNode(data)
}

export default getNode
