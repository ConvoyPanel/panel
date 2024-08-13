import { useParams } from '@tanstack/react-router'
import useSWR from 'swr'

import getAddresses from '@/api/servers/getAddresses.ts'


export const getKey = (uuid: string) => ['server.addresses', uuid]

const useAddressesSWR = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useSWR(getKey(serverUuid), () => getAddresses(serverUuid))
}

export default useAddressesSWR
