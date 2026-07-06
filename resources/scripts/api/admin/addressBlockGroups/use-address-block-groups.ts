import { keepPreviousData, useQuery } from '@tanstack/react-query'

import getAddressBlockGroups, {
    AddressBlockGroupQueryParams,
} from '@/api/admin/addressBlockGroups/getAddressBlockGroups.ts'

export const getKey = (params: AddressBlockGroupQueryParams) => [
    'address-block-groups',
    params,
]

const useAddressBlockGroups = (params: AddressBlockGroupQueryParams) => {
    return useQuery({
        queryKey: getKey(params),
        queryFn: () => getAddressBlockGroups(params),
        placeholderData: keepPreviousData,
    })
}

export default useAddressBlockGroups
