import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'

import getAddressBlockGroup from '@/api/admin/addressBlockGroups/getAddressBlockGroup.ts'
import getAddressBlockGroups, {
    AddressBlockGroupQueryParams,
} from '@/api/admin/addressBlockGroups/getAddressBlockGroups.ts'
import getAttachedNodes, {
    AttachedNodesQueryParams,
} from '@/api/admin/addressBlockGroups/getAttachedNodes.ts'

export const addressBlockGroupQueries = {
    all: () => ['admin', 'address-block-groups'] as const,
    lists: () => [...addressBlockGroupQueries.all(), 'list'] as const,
    list: (params: AddressBlockGroupQueryParams) =>
        queryOptions({
            queryKey: [...addressBlockGroupQueries.lists(), params] as const,
            queryFn: () => getAddressBlockGroups(params),
            placeholderData: keepPreviousData,
        }),
    details: () => [...addressBlockGroupQueries.all(), 'detail'] as const,
    detail: (id: number) =>
        queryOptions({
            queryKey: [...addressBlockGroupQueries.details(), id] as const,
            queryFn: () => getAddressBlockGroup(id),
        }),
    nodes: (
        id: number | null | undefined,
        params: AttachedNodesQueryParams
    ) =>
        queryOptions({
            queryKey: [
                ...addressBlockGroupQueries.all(),
                id,
                'nodes',
                params,
            ] as const,
            queryFn: () => getAttachedNodes(id!, params),
            enabled: !!id,
            placeholderData: keepPreviousData,
        }),
}

const useAddressBlockGroups = (params: AddressBlockGroupQueryParams) =>
    useQuery(addressBlockGroupQueries.list(params))

export default useAddressBlockGroups
