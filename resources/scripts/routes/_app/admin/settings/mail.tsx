import { mailSettingsQuery } from '@/features/settings/api.ts'
import { createFileRoute } from '@tanstack/react-router'

import { queryClient } from '@/lib/query-client.ts'

export const Route = createFileRoute('/_app/admin/settings/mail')({
    loader: () => queryClient.ensureQueryData(mailSettingsQuery()),
    staticData: {
        title: 'Mail Settings',
    },
})
