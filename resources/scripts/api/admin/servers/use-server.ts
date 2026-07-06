import { useQuery } from '@tanstack/react-query'

import getServer from '@/api/admin/servers/getServer.ts'

export const getKey = (id: number | null | undefined) => ['server', id]

const useServer = (id: number | null) => {
    return useQuery({
        queryKey: getKey(id),
        queryFn: () => getServer(id!),
        enabled: id != null,
    })
}

export default useServer
