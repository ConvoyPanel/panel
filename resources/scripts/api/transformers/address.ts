import { Address, AddressVersion } from '@/types/address.ts'

import { rawDataToServer } from '@/api/transformers/server.ts'
import { rawDataToAddressBlock } from '@/api/transformers/address-block.ts'

export const rawDataToAddress = (data: any): Address => ({
    id: data.id,
    addressBlockId: data.address_block_id,
    serverId: data.server_id,
    version: data.version === 'ipv4' ? AddressVersion.IPv4 : AddressVersion.IPv6,
    ip: data.ip,
    prefixLength: data.prefix_length,
    gateway: data.gateway,
    macAddress: data.mac_address,
    server: data.server ? rawDataToServer(data.server.data) : undefined,
    addressBlock: data.address_block ? rawDataToAddressBlock(data.address_block.data) : undefined,
})
