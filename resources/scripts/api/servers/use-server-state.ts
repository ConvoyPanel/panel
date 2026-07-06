import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import { serverQueries } from '@/api/servers/use-server.ts'

const useServerState = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useQuery(serverQueries.state(serverUuid))
}

export default useServerState
