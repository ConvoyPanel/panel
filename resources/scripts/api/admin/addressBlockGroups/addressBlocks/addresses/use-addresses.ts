import { useParams } from '@tanstack/react-router'
import { keepPreviousData, useQuery } from '@tanstack/react-query'

import getAddresses, {
    AddressInclude,
    AddressQueryParams,
} from '@/api/admin/addressBlockGroups/addressBlocks/addresses/getAddresses.ts'

export const getKey = (
    blockId: number,
    params: AddressQueryParams,
    include?: AddressInclude[]
) => ['address-block-group.address-block.addresses', blockId, params, include]

const useAddresses = (params: AddressQueryParams, include?: AddressInclude[]) => {
    const { addressBlockGroupId, addressBlockId } = useParams({
        strict: false,
    }) as {
        addressBlockGroupId: number
        addressBlockId: number
    }

    return useQuery({
        queryKey: getKey(addressBlockId, params, include),
        queryFn: () =>
            getAddresses(addressBlockGroupId, addressBlockId, params, include),
        placeholderData: keepPreviousData,
    })
}

export default useAddresses
