import { useParams } from '@tanstack/react-router'
import useSWR from '@/lib/swr'

import getAddressBlocks, {
    AddressBlockQueryParams,
} from '@/api/admin/addressBlockGroups/addressBlocks/getAddressBlocks.ts'

export const getKey = (
    addressBlockGroupId: number,
    params: AddressBlockQueryParams
) => ['address-block-group.address-blocks', addressBlockGroupId, params]

const useAddressBlocksSWR = (params: AddressBlockQueryParams) => {
    const { addressBlockGroupId } = useParams({ strict: false }) as {
        addressBlockGroupId: number
    }

    return useSWR(getKey(addressBlockGroupId, params), () =>
        getAddressBlocks(addressBlockGroupId, params)
    )
}

export default useAddressBlocksSWR
