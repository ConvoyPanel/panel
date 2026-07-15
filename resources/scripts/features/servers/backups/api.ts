import { keepPreviousData, queryOptions } from '@tanstack/react-query'
import { z } from 'zod'

import { apiFetch, type DataResponse, type PaginatedResponse } from '@/lib/api'
import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'

import { rawDataToBackup } from '@/features/servers/transforms.ts'
import type { Backup, PaginatedBackups } from '@/features/servers/types.ts'
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

// Mirrors StoreBackupRequest. `mode` and `compression_type` are BOTH required by
// the backend and have no config-level default, so the form always sends them —
// the Advanced disclosure just decides whether the user gets to see them.
export const backupModes = ['snapshot', 'suspend', 'kill'] as const
export const compressionTypes = ['none', 'lzo', 'gzip', 'zstd'] as const

export const createBackupSchema = z.object({
    name: z.string().min(1, 'A name is required').max(40),
    mode: z.enum(backupModes),
    compressionType: z.enum(compressionTypes),
    isLocked: z.boolean(),
})

export type CreateBackupPayload = z.infer<typeof createBackupSchema>

// Snapshot keeps the guest running; zstd is Proxmox's modern default (fast, good
// ratio). These are what a user who never opens Advanced gets.
export const createBackupDefaults: CreateBackupPayload = {
    name: '',
    mode: 'snapshot',
    compressionType: 'zstd',
    isLocked: false,
}

export const createBackup = async (
    serverUuid: string,
    payload: CreateBackupPayload
): Promise<Backup> =>
    rawDataToBackup(
        (
            await apiFetch<DataResponse<unknown>>(
                BackupController.store(serverUuid),
                {
                    body: {
                        name: payload.name,
                        mode: payload.mode,
                        compression_type: payload.compressionType,
                        is_locked: payload.isLocked,
                    },
                }
            )
        ).data
    )
