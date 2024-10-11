import { PaginatedBackups } from '@/types/backup.ts'
import {
    QueryBuilderParams,
    getPaginationSet,
    withQueryBuilderParams,
} from '@/utils/http.ts'

import axios from '@/lib/axios.ts'

import { rawDataToBackup } from '@/api/transformers/backup.ts'


const getBackups = async (
    uuid: string,
    params?: QueryBuilderParams<'completed_at' | 'created_at'>
): Promise<PaginatedBackups> => {
    const { data } = await axios.get(`/api/client/servers/${uuid}/backups`, {
        params: withQueryBuilderParams(params),
    })

    return {
        items: data.data.map(rawDataToBackup),
        pagination: getPaginationSet(data.meta.pagination),
        backupCount: data.meta.backup_count,
    }
}

export default getBackups
