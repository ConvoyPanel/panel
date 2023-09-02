import { AddressInclude } from '@/api/admin/nodes/addresses/getAddresses'
import { AddressType, rawDataToAddress } from '@/api/server/getServer'
import http from '@/api/http'
import { z } from 'zod'
import { ipAddress, macAddress } from '@/util/validation'

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
    .discriminatedUnion('isBulkAction', [singleAddressSchema, multipleAddressesSchema])
    .and(baseSchema)

type CreateAddressParameters = z.infer<typeof schema> & {
    include?: AddressInclude[]
}

const createAddress = async (
    poolId: number,
    { isBulkAction, macAddress, serverId, include, ...payload }: CreateAddressParameters
) => {
    const {
        data: { data },
    } = await http.post(
        `/api/admin/address-pools/${poolId}/addresses`,
        {
            is_bulk_action: isBulkAction,
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

    return Array.isArray(data) ? data.map(rawDataToAddress) : rawDataToAddress(data)
}

export default createAddress
