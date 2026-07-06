import { useParams } from '@tanstack/react-router'
import { keepPreviousData, useQuery } from '@tanstack/react-query'

import getAddressBlocks, {
    AddressBlockQueryParams,
} from '@/api/admin/addressBlockGroups/addressBlocks/getAddressBlocks.ts'

export const getKey = (
    addressBlockGroupId: number,
    params: AddressBlockQueryParams
) => ['address-block-group.address-blocks', addressBlockGroupId, params]

const useAddressBlocks = (params: AddressBlockQueryParams) => {
    const { addressBlockGroupId } = useParams({ strict: false }) as {
        addressBlockGroupId: number
    }

    return useQuery({
        queryKey: getKey(addressBlockGroupId, params),
        queryFn: () => getAddressBlocks(addressBlockGroupId, params),
        placeholderData: keepPreviousData,
    })
}

export default useAddressBlocks
