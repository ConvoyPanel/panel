import axios from '@/lib/axios.ts'
import type { Node } from '@/types/node.ts'

const getAttachedNodes = async (location: number): Promise<Node[]> => {
    const { data: { data } } = await axios.get(
        `/api/admin/locations/${location}/nodes`
    )
    return data as Node[]
}

export default getAttachedNodes
