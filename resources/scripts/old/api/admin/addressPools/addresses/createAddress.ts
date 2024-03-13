import { ipAddress, macAddress } from '@/util/validation'
import { z } from 'zod'

import { AddressInclude } from '@/api/admin/nodes/addresses/getAddresses'
import http from '@/api/http'
import { Address, rawDataToAddress } from '@/api/server/getServer'


const baseSchema = z.object({
    type: z.enum(['ipv4', 'ipv6']),
    cidr: z.preprocess(Number, z.number().int().min(1).max(128)),
    gateway: ipAddress().nonempty().max(191),
    macAddress: macAddress().max(191).nullable().or(z.literal('')),
    serverId: z.literal('').or(z.preprocess(Number, z.number())).nullable(),
})

const singleAddressSchema = z.object({
    isBulkAction: z.literal(false),
    address: ipAddress().nonempty().max(191),
})

const multipleAddressesSchema = z.object({
    isBulkAction: z.literal(true),
    startingAddress: ipAddress().nonempty().max(191),
    endingAddress: ipAddress().nonempty().max(191),
})

export const schema = z
    .discriminatedUnion('isBulkAction', [
        singleAddressSchema,
        multipleAddressesSchema,
    ])
    .and(baseSchema)

type CreateAddressParameters = z.infer<typeof schema> & {
    startingAddress?: string | null
    endingAddress?: string | null
    include?: AddressInclude[]
}

const createAddress = async (
    poolId: number,
    {
        isBulkAction,
        startingAddress,
        endingAddress,
        macAddress,
        serverId,
        include,
        ...payload
    }: CreateAddressParameters
): Promise<Address | null> => {
    const {
        data: { data },
    } = await http.post(
        `/api/admin/address-pools/${poolId}/addresses`,
        {
            is_bulk_action: isBulkAction,
            starting_address: startingAddress,
            ending_address: endingAddress,
            mac_address: macAddress,
            server_id: serverId,
            ...payload,
        },
        {
            params: {
                include: include?.join(','),
            },
        }
    )

    return data ? rawDataToAddress(data) : null
}

export default createAddress