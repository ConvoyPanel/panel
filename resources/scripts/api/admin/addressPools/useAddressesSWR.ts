import useSWR, { Key, SWRResponse } from 'swr'
import getAddresses, { QueryParams } from '@/api/admin/addressPools/getAddresses'
import { useMatch } from 'react-router-dom'
import { AddressResponse } from '@/api/admin/nodes/addresses/getAddresses'
import { Optimistic } from '@/lib/swr'

export const getKey = (id: number, page?: number, query?: string): Key => [
    'admin.address-pools.addresses',
    id,
    page,
    query,
]

const useAddressesSWR = ({ page, query, ...params }: QueryParams) => {
    const match = useMatch('/admin/ipam/:id/*')
    const id = parseInt(match!.params.id!)

    return useSWR<AddressResponse>(getKey(id, page, query), () =>
        getAddresses(id, {
            page,
            query,
            ...params,
        })
    ) as Optimistic<SWRResponse<AddressResponse, any>>
}

export default useAddressesSWR
