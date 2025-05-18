import { useParams } from '@tanstack/react-router'
import useSWR from 'swr'

import getAddresses, {
    AddressInclude,
    AddressQueryParams,
} from '@/api/admin/addressBlockGroups/addressBlocks/addresses/getAddresses.ts'

export const getKey = (
    blockId: number,
    params: AddressQueryParams,
    include?: AddressInclude[]
) => ['address-block-group.address-block.addresses', blockId, params, include]

const useAddressesSWR = (params: AddressQueryParams) => {
    const { addressBlockGroupId, addressBlockId } = useParams({
        strict: false,
    }) as {
        addressBlockGroupId: number
        addressBlockId: number
    }

    return useSWR(getKey(addressBlockId, params), () =>
        getAddresses(addressBlockGroupId, addressBlockId, params)
    )
}

export default useAddressesSWR
