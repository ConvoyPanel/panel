import axios from '@/lib/axios.ts'
import type { Node } from '@/types/node.ts'

const getNode = async (id: number): Promise<Node> => {
    const { data: { data } } = await axios.get(`/api/admin/nodes/${id}`)
    return data as Node
}

export default getNode
