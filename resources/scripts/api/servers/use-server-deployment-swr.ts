import useSWR, { type SWRConfiguration } from 'swr'
import { useParams } from '@tanstack/react-router'
import getServerDeployment from '@/api/servers/getServerDeployment.ts'
import { Deployment } from '@/types/deployment'

export const getKey = (uuid: string) => ['server', uuid, 'deployment']

const useServerDeploymentSWR = (uuid?: string, config?: SWRConfiguration<Deployment>) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useSWR(getKey(serverUuid), () => getServerDeployment(serverUuid), config)
}

export default useServerDeploymentSWR
