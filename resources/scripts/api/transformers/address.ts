import { Address, AddressVersion } from '@/types/address.ts'

import { rawDataToServer } from '@/api/transformers/server.ts'

export const rawDataToAddress = (data: any): Address => ({
    id: data.id,
    addressBlockId: data.address_block_id,
    serverId: data.server_id,
    type: data.type === 'ipv4' ? AddressVersion.IPv4 : AddressVersion.IPv6,
    ip: data.ip,
    prefixLength: data.prefix_length,
    gateway: data.gateway,
    macAddress: data.mac_address,
    server: data.server ? rawDataToServer(data.server.data) : undefined,
})
