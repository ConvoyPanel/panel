import { useParams } from '@tanstack/react-router'
import { useQuery, type UseQueryOptions } from '@tanstack/react-query'

import { serverQueries } from '@/api/servers/use-server.ts'
import { Deployment } from '@/types/deployment'

const useServerDeployment = (
    uuid?: string,
    options?: Partial<UseQueryOptions<Deployment | null>>
) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useQuery({
        ...serverQueries.deployment(serverUuid),
        ...options,
    } as UseQueryOptions<Deployment | null>)
}

export default useServerDeployment
