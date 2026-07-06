import { useQuery } from '@tanstack/react-query'

import { locationQueries } from '@/api/admin/locations/use-locations.ts'

const useAttachedNodes = (location: number | null | undefined) =>
    useQuery(locationQueries.nodes(location))

export default useAttachedNodes
