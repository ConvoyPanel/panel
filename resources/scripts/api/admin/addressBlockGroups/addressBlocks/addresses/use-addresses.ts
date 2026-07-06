import { useParams } from '@tanstack/react-router'
import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'

import getAddresses, {
    AddressInclude,
    AddressQueryParams,
} from '@/api/admin/addressBlockGroups/addressBlocks/addresses/getAddresses.ts'

export const addressQueries = {
    all: (groupId: number, blockId: number) =>
        [
            'admin',
            'address-block-groups',
            groupId,
            'address-blocks',
            blockId,
            'addresses',
        ] as const,
    list: (
        groupId: number,
        blockId: number,
        params: AddressQueryParams,
        include?: AddressInclude[]
    ) =>
        queryOptions({
            queryKey: [
                ...addressQueries.all(groupId, blockId),
                params,
                include,
            ] as const,
            queryFn: () => getAddresses(groupId, blockId, params, include),
            placeholderData: keepPreviousData,
        }),
}

const useAddresses = (
    params: AddressQueryParams,
    include?: AddressInclude[]
) => {
    const { addressBlockGroupId, addressBlockId } = useParams({
        strict: false,
    }) as {
        addressBlockGroupId: number
        addressBlockId: number
    }

    return useQuery(
        addressQueries.list(
            addressBlockGroupId,
            addressBlockId,
            params,
            include
        )
    )
}

export default useAddresses
