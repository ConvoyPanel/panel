import VersionController from '@/wayfinder/actions/App/Http/Controllers/Admin/VersionController'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { type DataResponse, apiFetch } from '@/lib/api'

export type UpdateStatus = App.Data.Admin.UpdateStatusData

// Served under both the panel (`/api/admin`) and Application (`/api/application`)
// prefixes, so Wayfinder emits URI-keyed dictionaries — reference the admin one.
const showRoute = VersionController.show['/api/admin/version']
const checkRoute = VersionController.check['/api/admin/version/check']

const getUpdateStatus = async (): Promise<UpdateStatus> =>
    (await apiFetch<DataResponse<UpdateStatus>>(showRoute())).data

export const updateQueries = {
    all: () => ['admin', 'updates'] as const,

    status: () =>
        queryOptions({
            queryKey: [...updateQueries.all(), 'status'] as const,
            queryFn: getUpdateStatus,
            // The answer changes when a release is cut, not by the minute, and
            // the endpoint only ever serves what the hourly check wrote. Poll
            // rarely; the Settings screen has an explicit refresh for the rest.
            staleTime: 5 * 60_000,
        }),
}

export const useUpdateStatus = () => useQuery(updateQueries.status())

/** Checks now instead of waiting for the next scheduled pass. */
export const checkForUpdates = async (): Promise<UpdateStatus> =>
    (await apiFetch<DataResponse<UpdateStatus>>(checkRoute())).data
