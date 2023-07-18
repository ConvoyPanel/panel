import useSWR, { Key, SWRResponse } from 'swr'
import { useMatch } from 'react-router-dom'
import { Optimistic } from '@/lib/swr'
import { AddressPool } from '@/api/admin/addressPools/getAddressPools'
import getAddressPool from '@/api/admin/addressPools/getAddressPool'

export const getKey = (id: number): Key => ['admin.address-pools', id]

const useAddressPoolSWR = () => {
    const match = useMatch('/admin/ipam/:id/*')
    const id = parseInt(match!.params.id!)

    return useSWR<AddressPool>(getKey(id), () => getAddressPool(id)) as Optimistic<SWRResponse<AddressPool, any>>
}

export default useAddressPoolSWR
