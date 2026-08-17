import UpdateStatusCard from '@/features/updates/components/UpdateStatusCard.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Heading } from '@/components/ui/Typography'

const UpdateSettingsPage = () => (
    <div className={'@container space-y-4'}>
        <Heading>Updates</Heading>
        <UpdateStatusCard />
    </div>
)

export const Route = createLazyFileRoute('/_app/admin/settings/updates')({
    component: UpdateSettingsPage,
})
