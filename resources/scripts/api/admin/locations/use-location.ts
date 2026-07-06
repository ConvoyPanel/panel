import { useQuery } from '@tanstack/react-query'

import { locationQueries } from '@/api/admin/locations/use-locations.ts'

const useLocation = (id?: number | null) => useQuery(locationQueries.detail(id))

export default useLocation
