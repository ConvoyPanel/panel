import useSWR from 'swr'

import getAddressPools, {
    AddressPoolResponse,
    QueryParams,
} from '@/api/admin/addressPools/getAddressPools'

const useAddressPoolsSWR = ({ page, query, ...params }: QueryParams) => {
    return useSWR<AddressPoolResponse>(
        ['admin.address-pools', page, query],
        () =>
            getAddressPools({
                page,
                query,
                ...params,
            })
    )
}

export default useAddressPoolsSWR