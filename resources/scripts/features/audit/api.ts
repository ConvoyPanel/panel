import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'
import AdminAuditLogController from '@/wayfinder/actions/App/Http/Controllers/Admin/AuditLogController'
import ClientAuditLogController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/AuditLogController'
import { keepPreviousData, queryOptions } from '@tanstack/react-query'

import { type PaginatedResponse, apiFetch } from '@/lib/api'

export interface AuditEntry {
    id: number
    event: App.Enums.Audit.AuditEvent
    batch: string | null
    actor: App.Data.Audit.AuditActorData
    subject: App.Data.Audit.AuditSubjectData | null
    properties: Record<string, unknown>
    ip: string | null
    userAgent: string | null
    createdAt: Date
}

export interface PaginatedAuditEntries {
    items: AuditEntry[]
    pagination: PaginatedResponse<unknown>['pagination']
}

const rawDataToAuditEntry = (data: App.Data.Audit.AuditLogData): AuditEntry => ({
    ...data,
    createdAt: new Date(data.createdAt),
})

/**
 * First type argument is the allowed *filter* keys, second the sort keys — they must match what the
 * controllers whitelist, or a typo silently returns an unfiltered list.
 */
export type AuditListParams = QueryBuilderParams<
    'event' | 'batch' | 'area' | 'actor_id' | 'subject_id' | 'subject_type' | 'since' | 'until',
    'created_at'
>

const toPaginated = (
    res: PaginatedResponse<App.Data.Audit.AuditLogData>
): PaginatedAuditEntries => ({
    items: res.items.map(rawDataToAuditEntry),
    pagination: res.pagination,
})

export const serverAuditQueries = {
    all: (serverUuid: string) => ['server', serverUuid, 'audit-logs'] as const,

    list: (serverUuid: string, params: AuditListParams = {}) =>
        queryOptions({
            queryKey: [
                ...serverAuditQueries.all(serverUuid),
                'list',
                params,
            ] as const,
            queryFn: async (): Promise<PaginatedAuditEntries> =>
                toPaginated(
                    await apiFetch<
                        PaginatedResponse<App.Data.Audit.AuditLogData>
                    >(ClientAuditLogController(serverUuid), {
                        params: withQueryBuilderParams(params),
                    })
                ),
            // Keeps the previous page on screen while the next one loads, so
            // paging through a feed doesn't flash an empty list.
            placeholderData: keepPreviousData,
        }),
}

/**
 * The admin controller also serves /api/application/audit-logs (the token-authenticated mirror), so
 * Wayfinder emits a dictionary keyed by URI rather than a plain callable. The panel talks to the
 * session-authenticated route.
 */
const adminAuditLogRoute = AdminAuditLogController['/api/admin/audit-logs']

export const adminAuditQueries = {
    all: () => ['admin', 'audit-logs'] as const,

    list: (params: AuditListParams = {}) =>
        queryOptions({
            queryKey: [...adminAuditQueries.all(), 'list', params] as const,
            queryFn: async (): Promise<PaginatedAuditEntries> =>
                toPaginated(
                    await apiFetch<
                        PaginatedResponse<App.Data.Audit.AuditLogData>
                    >(adminAuditLogRoute(), {
                        params: withQueryBuilderParams(params),
                    })
                ),
            placeholderData: keepPreviousData,
        }),
}
