import type { AddressMap } from '@/types/address-map.ts'
import type { Address, PaginatedAddresses } from '@/types/address.ts'
import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'
import AddressController from '@/wayfinder/actions/App/Http/Controllers/Admin/AddressController'
import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'
import { useParams } from '@tanstack/react-router'

import { type DataResponse, type PaginatedResponse, apiFetch } from '@/lib/api'
import {
    rawDataToAddress,
    rawDataToGeneratedAddressesResult,
} from '@/lib/transformers/address.ts'

/**
 * `state` is the operator's four states, not the column's three — `system` is a reserved row the
 * panel made and nobody can release, and it filters as its own thing. The faceted filter sends
 * these as an array.
 */
export type AddressQueryParams = QueryBuilderParams<
    'ip' | 'server_id' | 'state'
>

export type AddressBulkAction = 'reserve' | 'release' | 'delete'

export interface AddressBulkResult {
    action: AddressBulkAction
    affected: number
    /** Rows the action did not apply to — a system reservation, an assigned address. */
    skipped: number
}

export type AddressInclude = 'server' | 'addressBlock'

// AddressController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const indexRoute =
    AddressController.index[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks/{address_block}/addresses'
    ]
const mapRoute =
    AddressController.map[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks/{address_block}/addresses/map'
    ]
const bulkRoute =
    AddressController.bulk[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks/{address_block}/addresses/bulk'
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
const reserveRoute =
    AddressController.reserve[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks/{address_block}/addresses/{address}/reserve'
    ]
const unreserveRoute =
    AddressController.unreserve[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks/{address_block}/addresses/{address}/reserve'
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

/**
 * Every unit of a block, for the map view.
 *
 * Not paginated and not filtered: the whole point is to see the space at once, and the server
 * refuses (via `sparse` / `tooLarge`) rather than returning a grid too big to read.
 */
export const getAddressMap = async (
    blockGroupId: number,
    blockId: number
): Promise<AddressMap> =>
    apiFetch<AddressMap>(
        mapRoute({
            address_block_group: blockGroupId,
            address_block: blockId,
        })
    )

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
    map: (groupId: number, blockId: number) =>
        queryOptions({
            queryKey: [...addressQueries.all(groupId, blockId), 'map'] as const,
            queryFn: () => getAddressMap(groupId, blockId),
        }),
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

/**
 * Reserve, release or delete a selection in one request — and one audit entry.
 *
 * The server skips rows the action cannot touch rather than rejecting the batch, and reports how
 * many, so the caller can say what actually happened.
 */
export const bulkAddresses = async (
    blockGroupId: number,
    blockId: number,
    action: AddressBulkAction,
    ids: number[]
): Promise<AddressBulkResult> => {
    const { data } = await apiFetch<DataResponse<AddressBulkResult>>(
        bulkRoute({
            address_block_group: blockGroupId,
            address_block: blockId,
        }),
        { body: { action, ids } }
    )

    return data
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

export const reserveAddress = async (
    blockGroupId: number,
    blockId: number,
    addressId: number
): Promise<Address> => {
    const { data } = await apiFetch<DataResponse<unknown>>(
        reserveRoute({
            address_block_group: blockGroupId,
            address_block: blockId,
            address: addressId,
        })
    )

    return rawDataToAddress(data)
}

export const unreserveAddress = async (
    blockGroupId: number,
    blockId: number,
    addressId: number
): Promise<Address> => {
    const { data } = await apiFetch<DataResponse<unknown>>(
        unreserveRoute({
            address_block_group: blockGroupId,
            address_block: blockId,
            address: addressId,
        })
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
