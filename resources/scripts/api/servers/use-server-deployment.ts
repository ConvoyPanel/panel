import { useParams } from '@tanstack/react-router'
import { useQuery, type UseQueryOptions } from '@tanstack/react-query'

import getServerDeployment from '@/api/servers/getServerDeployment.ts'
import { Deployment } from '@/types/deployment'

export const getKey = (uuid: string) => ['server', uuid, 'deployment']

const useServerDeployment = (
    uuid?: string,
    options?: Partial<UseQueryOptions<Deployment | null>>
) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useQuery({
        queryKey: getKey(serverUuid),
        queryFn: () => getServerDeployment(serverUuid),
        ...options,
    })
}

export default useServerDeployment
