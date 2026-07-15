import { keepPreviousData, queryOptions } from '@tanstack/react-query'

import { apiFetch, type PaginatedResponse } from '@/lib/api'
import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'

import { rawDataToBackup } from '@/features/servers/transforms.ts'
import type { PaginatedBackups } from '@/features/servers/types.ts'
import BackupController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/BackupController'

export type BackupListParams = QueryBuilderParams<'completed_at' | 'created_at'>

// The index endpoint augments the standard paginated envelope with server-wide
// backup count and size totals (used for the quota display), and every backup
// row needs date/enum normalization via rawDataToBackup.
type RawBackupsResponse = PaginatedResponse<unknown> & {
    backupCount: number
    backupSize: number
}

export const backupQueries = {
    all: (serverUuid: string) => ['server', serverUuid, 'backups'] as const,

    list: (serverUuid: string, params: BackupListParams = {}) =>
        queryOptions({
            queryKey: [
                ...backupQueries.all(serverUuid),
                'list',
                params,
            ] as const,
            queryFn: async (): Promise<PaginatedBackups> => {
                const res = await apiFetch<RawBackupsResponse>(
                    BackupController.index(serverUuid),
                    { params: withQueryBuilderParams(params) }
                )

                return {
                    items: res.items.map(rawDataToBackup),
                    pagination: res.pagination,
                    backupCount: res.backupCount,
                    backupSize: res.backupSize,
                }
            },
            placeholderData: keepPreviousData,
        }),
}
