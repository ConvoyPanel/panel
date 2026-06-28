import { Node } from '@/types/node'
import useSWR from '@/lib/swr'

import getAttachedNodes from './getAttachedNodes'

export const getKey = (location: number | null | undefined) =>
    location ? ['locations.nodes', location] : null

const useAttachedNodesSWR = (location: number | null | undefined) => {
    return useSWR<Node[]>(getKey(location), () => getAttachedNodes(location!))
}

export default useAttachedNodesSWR
