import { telemetrySettingsQuery } from '@/features/settings/api.ts'
import { createFileRoute } from '@tanstack/react-router'

import { queryClient } from '@/lib/query-client.ts'

export const Route = createFileRoute('/_app/admin/settings/telemetry')({
    loader: () => queryClient.ensureQueryData(telemetrySettingsQuery()),
    staticData: {
        title: 'Telemetry',
    },
})
