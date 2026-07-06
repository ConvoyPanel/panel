import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import getAddressBlockGroup from '@/api/admin/addressBlockGroups/getAddressBlockGroup.ts'

export const getKey = (id: number) => ['address-block-group', id]

export const preloadAddressBlockGroup = (id: number) =>
    queryClient.prefetchQuery({
        queryKey: getKey(id),
        queryFn: () => getAddressBlockGroup(id),
    })

const useAddressBlockGroup = () => {
    const params = useParams({ strict: false }) as {
        addressBlockGroupId: number
    }

    return useQuery({
        queryKey: getKey(params.addressBlockGroupId),
        queryFn: () => getAddressBlockGroup(params.addressBlockGroupId),
    })
}

export default useAddressBlockGroup
