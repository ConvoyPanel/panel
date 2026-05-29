import useSWR from 'swr'

import getOverview, { DashboardOverview } from '@/api/admin/overview/getOverview'

const useOverviewSWR = () => {
    return useSWR<DashboardOverview>('admin:overview', getOverview)
}

export default useOverviewSWR
