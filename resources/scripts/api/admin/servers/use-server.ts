import { useQuery } from '@tanstack/react-query'

import { serverQueries } from '@/api/admin/servers/use-servers.ts'

const useServer = (id: number | null) => useQuery(serverQueries.detail(id))

export default useServer
