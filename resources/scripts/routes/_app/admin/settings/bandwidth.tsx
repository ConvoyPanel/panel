import { createFileRoute } from '@tanstack/react-router'

import { settingsQueries } from '@/features/settings/api.ts'
import { queryClient } from '@/lib/query-client.ts'

export const Route = createFileRoute('/_app/admin/settings/bandwidth')({
    loader: () => queryClient.ensureQueryData(settingsQueries.bandwidth()),
    staticData: {
        title: 'Bandwidth Settings',
    },
})
