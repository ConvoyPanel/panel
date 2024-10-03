import { useParams } from '@tanstack/react-router'
import useSWR from 'swr'

import getSnapshots from '@/api/servers/snapshots/getSnapshots.ts'


export const getKey = (uuid: string) => ['servers.snapshots', uuid]

const useSnapshotsSWR = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useSWR(getKey(serverUuid), () => getSnapshots(serverUuid))
}

export default useSnapshotsSWR
