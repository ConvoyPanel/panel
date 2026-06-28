import useSWR from '@/lib/swr'

import getAddressBlockGroups, {
    AddressBlockGroupQueryParams,
} from '@/api/admin/addressBlockGroups/getAddressBlockGroups.ts'

export const getKey = (params: AddressBlockGroupQueryParams) => [
    'address-block-groups',
    params,
]

const useAddressBlockGroupsSWR = (params: AddressBlockGroupQueryParams) => {
    return useSWR(getKey(params), () => getAddressBlockGroups(params))
}

export default useAddressBlockGroupsSWR
