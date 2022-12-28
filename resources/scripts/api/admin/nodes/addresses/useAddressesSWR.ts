import getAddresses, { AddressResponse, QueryParams } from '@/api/admin/nodes/addresses/getAddresses'
import useSWR from 'swr'

const useAddressesSWR = (nodeId: number, {page, ...params}: QueryParams) => {
    return useSWR<AddressResponse>(['admin:node:addresses', nodeId, page], () => getAddresses(nodeId, {page, ...params}))
}

export default useAddressesSWR