import { Optimistic } from '@/lib/swr'
import { useMatch } from 'react-router-dom'
import useSWR, { Key, SWRResponse } from 'swr'

import getAddressPool from '@/api/admin/addressPools/getAddressPool'
import { AddressPool } from '@/api/admin/addressPools/getAddressPools'


export const getKey = (id: number): Key => ['admin.address-pools', id]

const useAddressPoolSWR = () => {
    const match = useMatch('/admin/ipam/:id/*')
    const id = parseInt(match!.params.id!)

    return useSWR<AddressPool>(getKey(id), () => getAddressPool(id), {
        revalidateOnMount: false,
    }) as Optimistic<SWRResponse<AddressPool, any>>
}

export default useAddressPoolSWR