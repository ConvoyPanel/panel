import { Node } from '@/types/node'
import { useQuery } from '@tanstack/react-query'

import getAttachedNodes from './getAttachedNodes'

export const getKey = (location: number | null | undefined) => [
    'locations.nodes',
    location,
]

const useAttachedNodes = (location: number | null | undefined) => {
    return useQuery<Node[]>({
        queryKey: getKey(location),
        queryFn: () => getAttachedNodes(location!),
        enabled: !!location,
    })
}

export default useAttachedNodes
