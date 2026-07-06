import { useParams } from '@tanstack/react-router'
import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'

import getAddressBlock from '@/api/admin/addressBlockGroups/addressBlocks/getAddressBlock.ts'
import getAddressBlocks, {
    AddressBlockQueryParams,
} from '@/api/admin/addressBlockGroups/addressBlocks/getAddressBlocks.ts'

export const addressBlockQueries = {
    all: (groupId: number) =>
        ['admin', 'address-block-groups', groupId, 'address-blocks'] as const,
    lists: (groupId: number) =>
        [...addressBlockQueries.all(groupId), 'list'] as const,
    list: (groupId: number, params: AddressBlockQueryParams) =>
        queryOptions({
            queryKey: [...addressBlockQueries.lists(groupId), params] as const,
            queryFn: () => getAddressBlocks(groupId, params),
            placeholderData: keepPreviousData,
        }),
    detail: (groupId: number, blockId: number) =>
        queryOptions({
            queryKey: [
                ...addressBlockQueries.all(groupId),
                'detail',
                blockId,
            ] as const,
            queryFn: () => getAddressBlock(groupId, blockId),
        }),
}

const useAddressBlocks = (params: AddressBlockQueryParams) => {
    const { addressBlockGroupId } = useParams({ strict: false }) as {
        addressBlockGroupId: number
    }

    return useQuery(addressBlockQueries.list(addressBlockGroupId, params))
}

export default useAddressBlocks
