import { createLazyFileRoute } from '@tanstack/react-router'

import OverviewContainer from '@/components/interfaces/Admin/Dashboard/OverviewContainer'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/')({
    component: OverviewContainer,
})
