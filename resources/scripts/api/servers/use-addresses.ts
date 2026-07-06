import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import getAddresses from '@/api/servers/getAddresses.ts'


export const getKey = (uuid: string) => ['server.addresses', uuid]

const useAddresses = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useQuery({
        queryKey: getKey(serverUuid),
        queryFn: () => getAddresses(serverUuid),
    })
}

export default useAddresses
