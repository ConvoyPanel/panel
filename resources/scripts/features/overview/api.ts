import { queryOptions } from '@tanstack/react-query'

import { apiFetch, type DataResponse } from '@/lib/api'
import OverviewController from '@/wayfinder/actions/App/Http/Controllers/Admin/OverviewController'

export type OverviewData = App.Data.Admin.Overview.OverviewData

export const overviewQueries = {
    all: () => ['admin', 'overview'] as const,

    metrics: () =>
        queryOptions({
            queryKey: [...overviewQueries.all(), 'metrics'] as const,
            queryFn: async () =>
                (await apiFetch<DataResponse<OverviewData>>(OverviewController()))
                    .data,
            // The endpoint caches for 15s; refresh a touch slower than that.
            refetchInterval: 30_000,
        }),
}
