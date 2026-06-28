import type { PaginatedBackups, Backup } from '@/types/backup.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'

import axios from '@/lib/axios.ts'
import type { PaginatedResponse } from '@/lib/api.ts'

import { rawDataToBackup } from '@/api/transformers/backup.ts'

const getBackups = async (
    uuid: string,
    params?: QueryBuilderParams<'completed_at' | 'created_at'>
): Promise<PaginatedBackups> => {
    const { data } = await axios.get<PaginatedResponse<Backup> & { backupCount: number }>(
        `/api/client/servers/${uuid}/backups`,
        { params: withQueryBuilderParams(params) }
    )

    return {
        items: data.items.map(rawDataToBackup),
        pagination: data.pagination,
        backupCount: data.backupCount,
    }
}

export default getBackups
