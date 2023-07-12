import getAddressPools, { AddressPoolResponse, QueryParams } from '@/api/admin/nodes/addressPools/getAddressPools'
import useSWR from 'swr'

const useAddressPoolsSWR = ({ page, query, ...params }: QueryParams) => {
    return useSWR<AddressPoolResponse>(['admin.address-pools', page, query], () =>
        getAddressPools({
            page,
            query,
            ...params,
        })
    )
}

export default useAddressPoolsSWR
