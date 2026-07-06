import { useParams } from '@tanstack/react-router'
import {
    keepPreviousData,
    queryOptions,
    useQuery,
} from '@tanstack/react-query'

import {
    rawDataToAddress,
    rawDataToGeneratedAddressesResult,
} from '@/lib/transformers/address.ts'
import { apiFetch, type DataResponse, type PaginatedResponse } from '@/lib/api'
import type { Address, PaginatedAddresses } from '@/types/address.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'
import AddressController from '@/wayfinder/actions/App/Http/Controllers/Admin/AddressController'

export type AddressQueryParams = QueryBuilderParams<'ip' | 'server_id'>

export type AddressInclude = 'server' | 'addressBlock'

// AddressController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const indexRoute =
    AddressController.index[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks/{address_block}/addresses'
    ]
const generateRoute =
    AddressController.generate[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks/{address_block}/addresses/generate'
    ]
const updateRoute =
    AddressController.update[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks/{address_block}/addresses/{address}'
    ]
const destroyRoute =
    AddressController.destroy[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks/{address_block}/addresses/{address}'
    ]

export const getAddresses = async (
    blockGroupId: number,
    blockId: number,
    params: AddressQueryParams,
    include?: AddressInclude[]
): Promise<PaginatedAddresses> => {
    const res = await apiFetch<PaginatedResponse<Address>>(
        indexRoute({
            address_block_group: blockGroupId,
            address_block: blockId,
        }),
        {
            params: {
                ...withQueryBuilderParams(params),
                include: include?.join(','),
            },
        }
    )

    return {
        items: res.items.map(rawDataToAddress),
        pagination: res.pagination,
    }
}

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

export const useAddresses = (
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

export const generateAddresses = async (
    blockGroupId: number,
    blockId: number
) => {
    const { data } = await apiFetch<DataResponse<unknown>>(
        generateRoute({
            address_block_group: blockGroupId,
            address_block: blockId,
        })
    )

    return rawDataToGeneratedAddressesResult(data)
}

export const updateAddress = async (
    blockGroupId: number,
    blockId: number,
    addressId: number,
    serverId: number | null
) => {
    const { data } = await apiFetch<DataResponse<unknown>>(
        updateRoute({
            address_block_group: blockGroupId,
            address_block: blockId,
            address: addressId,
        }),
        {
            body: {
                server_id: serverId,
            },
        }
    )

    return rawDataToAddress(data)
}

export const deleteAddress = async (
    blockGroupId: number,
    blockId: number,
    addressId: number
): Promise<void> => {
    await apiFetch(
        destroyRoute({
            address_block_group: blockGroupId,
            address_block: blockId,
            address: addressId,
        })
    )
}
