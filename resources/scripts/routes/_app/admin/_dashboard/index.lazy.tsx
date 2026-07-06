import { createLazyFileRoute } from '@tanstack/react-router'

import OverviewContainer from '@/features/overview/components/admin/OverviewContainer'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/')({
    component: OverviewContainer,
})
