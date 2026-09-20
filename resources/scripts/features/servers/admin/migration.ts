import ServerMigrationController from '@/wayfinder/actions/App/Http/Controllers/Admin/ServerMigrationController'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { type DataResponse, apiFetch } from '@/lib/api'

export type MigrationPlan = App.Data.Server.Migration.MigrationPlanData
export type MigrationCandidate =
    App.Data.Server.Migration.MigrationCandidateData
export type MigrationPreview = App.Data.Server.Migration.MigrationPreviewData
export type MigrationAddress = App.Data.Server.Migration.MigrationAddressData
export type MigrationDisposition = App.Enums.Server.MigrationDisposition

// ServerMigrationController is served under both the panel (`/api/admin`) and
// the Application token API (`/api/application`), so Wayfinder emits URI-keyed
// dictionaries instead of callables — the panel references the admin route.
const planRoute =
    ServerMigrationController.index['/api/admin/servers/{server}/migration']
const previewRoute =
    ServerMigrationController.show[
        '/api/admin/servers/{server}/migration/{destination}'
    ]
const migrateRoute =
    ServerMigrationController.store['/api/admin/servers/{server}/migration']

/**
 * spatie/laravel-data wraps nested data collections in their own `data` key, so
 * a collection arrives as `{ data: [...] }` while the generated type says
 * `T[]`. Same unwrap a deployment's steps need.
 */
const unwrap = <T>(value: unknown): T[] => {
    if (Array.isArray(value)) return value as T[]
    if (value && typeof value === 'object' && 'data' in value) {
        return ((value as { data: T[] }).data ?? []) as T[]
    }

    return []
}

const toPlan = (raw: any): MigrationPlan => ({
    ...raw,
    localResources: unwrap<string>(raw.localResources),
    candidates: unwrap<MigrationCandidate>(raw.candidates),
})

const toPreview = (raw: any): MigrationPreview => ({
    ...raw,
    preserved: unwrap<MigrationAddress>(raw.preserved),
    released: unwrap<MigrationAddress>(raw.released),
    allocated: unwrap<MigrationAddress>(raw.allocated),
})

const getMigrationPlan = async (uuid: string): Promise<MigrationPlan> =>
    toPlan((await apiFetch<DataResponse<any>>(planRoute(uuid))).data)

const getMigrationPreview = async (
    uuid: string,
    nodeId: number
): Promise<MigrationPreview> =>
    toPreview(
        (await apiFetch<DataResponse<any>>(previewRoute([uuid, nodeId]))).data
    )

export const migrationQueries = {
    all: () => ['admin', 'server-migration'] as const,
    plan: (uuid: string | null | undefined) =>
        queryOptions({
            queryKey: [...migrationQueries.all(), 'plan', uuid] as const,
            queryFn: () => getMigrationPlan(uuid!),
            enabled: !!uuid,
            // The verdict turns on live Proxmox state and on pool attachments
            // an operator may be editing in another tab; a cached one is a
            // dialog telling somebody something that is no longer true.
            staleTime: 0,
        }),
    preview: (uuid: string | null | undefined, nodeId: number | null) =>
        queryOptions({
            queryKey: [
                ...migrationQueries.all(),
                'preview',
                uuid,
                nodeId,
            ] as const,
            queryFn: () => getMigrationPreview(uuid!, nodeId!),
            enabled: !!uuid && nodeId != null,
            staleTime: 0,
        }),
}

export const useMigrationPlan = (uuid: string | null | undefined) =>
    useQuery(migrationQueries.plan(uuid))

export const useMigrationPreview = (
    uuid: string | null | undefined,
    nodeId: number | null
) => useQuery(migrationQueries.preview(uuid, nodeId))

export const migrateServer = async (
    uuid: string,
    nodeId: number,
    acknowledgeAddressChange: boolean
): Promise<void> => {
    await apiFetch(migrateRoute(uuid), {
        body: {
            node_id: nodeId,
            acknowledge_address_change: acknowledgeAddressChange,
        },
    })
}
