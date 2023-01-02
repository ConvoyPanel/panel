import getAddresses, { AddressResponse, QueryParams } from '@/api/admin/nodes/addresses/getAddresses'
import useSWR from 'swr'

interface Params extends QueryParams {
    id?: string | number
}

const useAddressesSWR = (nodeId: number, { page, id, ...params }: Params) => {
    return useSWR<AddressResponse>(['admin:node:addresses', nodeId, page, id], () =>
        getAddresses(nodeId, { page, ...params })
    )
}

export default useAddressesSWR
