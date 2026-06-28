import { useParams } from '@tanstack/react-router'
import useSWR from '@/lib/swr'

import getAddresses, {
    AddressInclude,
    AddressQueryParams,
} from '@/api/admin/addressBlockGroups/addressBlocks/addresses/getAddresses.ts'

export const getKey = (
    blockId: number,
    params: AddressQueryParams,
    include?: AddressInclude[]
) => ['address-block-group.address-block.addresses', blockId, params, include]

const useAddressesSWR = (params: AddressQueryParams, include?: AddressInclude[]) => {
    const { addressBlockGroupId, addressBlockId } = useParams({
        strict: false,
    }) as {
        addressBlockGroupId: number
        addressBlockId: number
    }

    return useSWR(getKey(addressBlockId, params, include), () =>
        getAddresses(addressBlockGroupId, addressBlockId, params, include)
    )
}

export default useAddressesSWR
