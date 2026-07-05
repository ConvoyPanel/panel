import { queryOptions } from '@tanstack/react-query'

import { apiFetch, type DataResponse } from '@/lib/api'
import OverviewController from '@/wayfinder/actions/App/Http/Controllers/Admin/OverviewController'

export type OverviewData = App.Data.Admin.Overview.OverviewData

// This controller is also exposed on the Application (token) API, so Wayfinder
// emits a URI-keyed dictionary rather than a callable — the panel uses the
// /api/admin route.
const overviewRoute = OverviewController['/api/admin/overview']

export const overviewQueries = {
    all: () => ['admin', 'overview'] as const,

    metrics: () =>
        queryOptions({
            queryKey: [...overviewQueries.all(), 'metrics'] as const,
            queryFn: async () =>
                (await apiFetch<DataResponse<OverviewData>>(overviewRoute()))
                    .data,
            // The endpoint caches for 15s; refresh a touch slower than that.
            refetchInterval: 30_000,
        }),
}
