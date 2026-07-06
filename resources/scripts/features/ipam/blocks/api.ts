import { useParams } from '@tanstack/react-router'
import {
    keepPreviousData,
    queryOptions,
    useQuery,
} from '@tanstack/react-query'
import { z } from 'zod'

import { rawDataToAddressBlock } from '@/lib/transformers/address-block.ts'
import { apiFetch, type DataResponse, type PaginatedResponse } from '@/lib/api'
import { queryClient } from '@/lib/query-client.ts'
import type { AddressBlock, PaginatedAddressBlocks } from '@/types/address-block.ts'
import { AddressVersion } from '@/types/address.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'
import AddressBlockController from '@/wayfinder/actions/App/Http/Controllers/Admin/AddressBlockController'

export type AddressBlockQueryParams = QueryBuilderParams<
    | '*'
    | 'name'
    | 'description'
    | 'type'
    | 'base_ip'
    | 'gateway'
    | 'mac_address'
    | 'prefix_length_to'
    | 'prefix_length_from'
>

export const addressBlockSchema = z
    .object({
        name: z.string().max(40),
        description: z.string().max(191),
        version: z.nativeEnum(AddressVersion),
        baseIp: z.string().min(1),
        gateway: z.string(),
        macAddress: z.string(),
        prefixLengthFrom: z.coerce.number().int().min(0).max(128),
        prefixLengthTo: z.coerce.number().int().min(0).max(128),
    })
    .superRefine((data, ctx) => {
        // IPv4 validation regex
        const ipv4Regex =
            /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/

        // IPv6 validation regex
        const ipv6Regex =
            /^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))$/

        if (data.version === AddressVersion.IPv4) {
            // Validate baseIp is IPv4
            if (!ipv4Regex.test(data.baseIp)) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: 'Base IP must be a valid IPv4 address',
                    path: ['baseIp'],
                })
            }

            // Validate gateway is IPv4 if provided
            if (data.gateway.length > 0 && !ipv4Regex.test(data.gateway)) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: 'Gateway must be a valid IPv4 address',
                    path: ['gateway'],
                })
            }
        } else if (data.version === AddressVersion.IPv6) {
            // Validate baseIp is IPv6
            if (!ipv6Regex.test(data.baseIp)) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: 'Base IP must be a valid IPv6 address',
                    path: ['baseIp'],
                })
            }

            // Validate gateway is IPv6 if provided
            if (data.gateway.length > 0 && !ipv6Regex.test(data.gateway)) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: 'Gateway must be a valid IPv6 address',
                    path: ['gateway'],
                })
            }
        }
    })

// AddressBlockController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const indexRoute =
    AddressBlockController.index[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks'
    ]
const showRoute =
    AddressBlockController.show[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks/{address_block}'
    ]
const storeRoute =
    AddressBlockController.store[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks'
    ]
const updateRoute =
    AddressBlockController.update[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks/{address_block}'
    ]
const destroyRoute =
    AddressBlockController.destroy[
        '/api/admin/address-block-groups/{address_block_group}/address-blocks/{address_block}'
    ]

export const getAddressBlocks = async (
    addressBlockGroupId: number,
    params: AddressBlockQueryParams
): Promise<PaginatedAddressBlocks> => {
    const res = await apiFetch<PaginatedResponse<AddressBlock>>(
        indexRoute(addressBlockGroupId),
        { params: withQueryBuilderParams(params) }
    )

    return { items: res.items, pagination: res.pagination }
}

const getAddressBlock = async (blockGroupId: number, blockId: number) =>
    rawDataToAddressBlock(
        (
            await apiFetch<DataResponse<unknown>>(
                showRoute({
                    address_block_group: blockGroupId,
                    address_block: blockId,
                })
            )
        ).data
    )

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

export const useAddressBlocks = (params: AddressBlockQueryParams) => {
    const { addressBlockGroupId } = useParams({ strict: false }) as {
        addressBlockGroupId: number
    }

    return useQuery(addressBlockQueries.list(addressBlockGroupId, params))
}

export const preloadAddressBlock = (blockGroupId: number, blockId: number) =>
    queryClient.prefetchQuery(addressBlockQueries.detail(blockGroupId, blockId))

export const useAddressBlock = () => {
    const params = useParams({ strict: false }) as {
        addressBlockGroupId: number
        addressBlockId: number
    }

    return useQuery(
        addressBlockQueries.detail(
            params.addressBlockGroupId,
            params.addressBlockId
        )
    )
}

export const createAddressBlock = async (
    addressBlockGroupId: number,
    {
        baseIp,
        macAddress,
        prefixLengthFrom,
        prefixLengthTo,
        ...params
    }: z.infer<typeof addressBlockSchema>
) =>
    rawDataToAddressBlock(
        (
            await apiFetch<DataResponse<unknown>>(
                storeRoute(addressBlockGroupId),
                {
                    body: {
                        ...params,
                        base_ip: baseIp,
                        mac_address: macAddress,
                        prefix_length_from: prefixLengthFrom,
                        prefix_length_to: prefixLengthTo,
                    },
                }
            )
        ).data
    )

export const updateAddressBlock = async (
    addressBlockGroupId: number,
    addressBlockId: number,
    {
        version: _,
        baseIp,
        macAddress,
        prefixLengthFrom,
        prefixLengthTo,
        ...params
    }: z.infer<typeof addressBlockSchema>
) =>
    rawDataToAddressBlock(
        (
            await apiFetch<DataResponse<unknown>>(
                updateRoute({
                    address_block_group: addressBlockGroupId,
                    address_block: addressBlockId,
                }),
                {
                    body: {
                        ...params,
                        base_ip: baseIp,
                        mac_address: macAddress,
                        prefix_length_from: prefixLengthFrom,
                        prefix_length_to: prefixLengthTo,
                    },
                }
            )
        ).data
    )

export const deleteAddressBlock = async (
    addressBlockGroupId: number,
    addressBlockId: number
): Promise<void> => {
    await apiFetch(
        destroyRoute({
            address_block_group: addressBlockGroupId,
            address_block: addressBlockId,
        })
    )
}
