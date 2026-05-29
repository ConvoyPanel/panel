import useSWR from 'swr'

import getOverview, { DashboardOverview } from '@/api/admin/overview/getOverview'

const useOverviewSWR = () => {
    return useSWR<DashboardOverview>('admin:overview', getOverview, {
        refreshInterval: 10000,
        revalidateOnFocus: true,
    })
}

export default useOverviewSWR
