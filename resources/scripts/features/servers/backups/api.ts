import { useServer } from '@/features/servers/detail/api.ts'
import { rawDataToBackup } from '@/features/servers/transforms.ts'
import type { Backup, PaginatedBackups } from '@/features/servers/types.ts'
import usePagination from '@/hooks/use-pagination.ts'
import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'
import BackupController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/BackupController'
import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'
import { useParams } from '@tanstack/react-router'
import { z } from 'zod'

import { type DataResponse, type PaginatedResponse, apiFetch } from '@/lib/api'

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

// backup_count_limit / backup_size_limit both store -1 for unlimited, against
// which a percentage — and a progress bar — is meaningless.
export const UNLIMITED = -1

export interface BackupQuota {
    /** Non-failed backups server-wide, NOT the length of the current page. */
    count: number
    size: number
    countLimit: number
    sizeLimit: number
    /**
     * A finite allowance a bar can be drawn against. Zero is deliberately NOT
     * one: `0 of 0` and an empty bar read as data when they mean "you were
     * never given any", so a limit of zero gets said in words instead.
     */
    hasCountLimit: boolean
    hasSizeLimit: boolean
    isCountUnlimited: boolean
    isSizeUnlimited: boolean
    /**
     * Every slot is in use, so BackupCreationService will refuse the next
     * backup. Storage is deliberately absent: the service only enforces the
     * count, and gating on a rule the API does not keep would block a request
     * that would have succeeded.
     */
    isAtCountLimit: boolean
    /**
     * The node has no backup-capable storage, so BackupCreationService throws a
     * 409 regardless of the quota. Not the user's doing and not something they
     * can fix, so the action stays visible and explains itself.
     */
    hasNoStorage: boolean
    /** No slots were ever allocated — there is no action to offer at all. */
    isUnavailable: boolean
}

/**
 * Joins the server's limits to the totals the index endpoint aggregates. Reads
 * the same query key as the list, so it is a cache read rather than a second
 * request.
 */
export const useBackupQuota = (): BackupQuota | undefined => {
    const { serverUuid } = useParams({ strict: false }) as {
        serverUuid: string
    }
    const { page } = usePagination()
    const { data: server } = useServer()
    const { data } = useQuery(backupQueries.list(serverUuid, { page }))

    if (!server || !data) return undefined

    const { countLimit, sizeLimit } = server.backup

    return {
        count: data.backupCount,
        size: data.backupSize,
        countLimit,
        sizeLimit,
        hasCountLimit: countLimit > 0,
        hasSizeLimit: sizeLimit > 0,
        isCountUnlimited: countLimit === UNLIMITED,
        isSizeUnlimited: sizeLimit === UNLIMITED,
        isAtCountLimit:
            countLimit !== UNLIMITED && data.backupCount >= countLimit,
        hasNoStorage: !server.backup.hasStorage,
        isUnavailable: countLimit === 0,
    }
}

/**
 * Copy for an inert Create button, or undefined when a backup may be created.
 * A server with no slots allocated renders no button at all, so it never needs
 * a reason. Storage comes first: a node with nowhere to write cannot run a
 * backup however many slots are free.
 */
export const quotaBlockedReason = (quota?: BackupQuota): string | undefined => {
    if (!quota || quota.isUnavailable) return undefined

    if (quota.hasNoStorage) {
        return 'This node has no storage configured for backups. Your administrator will need to add one.'
    }

    return quota.isAtCountLimit
        ? `All ${quota.countLimit} backup slots are in use. Delete a backup to make room.`
        : undefined
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
