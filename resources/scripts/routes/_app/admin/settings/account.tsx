import { accountSettingsQuery } from '@/features/settings/api.ts'
import { createFileRoute } from '@tanstack/react-router'

import { queryClient } from '@/lib/query-client.ts'

export const Route = createFileRoute('/_app/admin/settings/account')({
    loader: () => queryClient.ensureQueryData(accountSettingsQuery()),
    staticData: {
        title: 'Account Settings',
    },
})
