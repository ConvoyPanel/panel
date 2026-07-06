import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import { addressBlockGroupQueries } from '@/api/admin/addressBlockGroups/use-address-block-groups.ts'

export const preloadAddressBlockGroup = (id: number) =>
    queryClient.prefetchQuery(addressBlockGroupQueries.detail(id))

const useAddressBlockGroup = () => {
    const params = useParams({ strict: false }) as {
        addressBlockGroupId: number
    }

    return useQuery(addressBlockGroupQueries.detail(params.addressBlockGroupId))
}

export default useAddressBlockGroup
