import { QueryBuilderParams } from '@/utils/http.ts'
import { useParams } from '@tanstack/react-router'
import useSWR from 'swr'

import getBackups from '@/api/servers/backups/getBackups.ts'


const getKey = (
    uuid: string,
    params?: QueryBuilderParams<'completed_at' | 'created_at'>
) => ['backups', uuid, params]

const useBackupsSWR = (
    uuid?: string,
    params?: QueryBuilderParams<'completed_at' | 'created_at'>
) => {
    const urlParams = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? urlParams.serverUuid

    return useSWR(getKey(serverUuid, params), () =>
        getBackups(serverUuid, params)
    )
}

export default useBackupsSWR
