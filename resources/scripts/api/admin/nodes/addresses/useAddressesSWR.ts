import useSWR from 'swr'

import getAddresses, {
    AddressResponse,
    QueryParams,
} from '@/api/admin/nodes/addresses/getAddresses'
import { isValidNodeId } from '@/api/admin/nodes/isValidNodeId'

interface Params extends QueryParams {
    id?: string | number
}

const useAddressesSWR = (nodeId: number, { page, id, ...params }: Params) => {
    return useSWR<AddressResponse>(
        // See useTemplateGroupsSWR: skip the fetch until a real node is selected,
        // rather than requesting /api/admin/nodes/NaN/addresses.
        isValidNodeId(nodeId)
            ? ['admin:node:addresses', nodeId, page, id]
            : null,
        () => getAddresses(nodeId, { page, ...params })
    )
}

export default useAddressesSWR
