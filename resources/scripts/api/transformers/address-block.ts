import { AddressBlock } from '@/types/address-block.ts'
import { AddressVersion } from '@/types/address.ts'

export const rawDataToAddressBlock = (data: any): AddressBlock => ({
    id: data.id,
    addressBlockGroupId: data.address_block_group_id,
    name: data.name,
    description: data.description,
    version: data.version === 'ipv4' ? AddressVersion.IPv4 : AddressVersion.IPv6,
    baseIp: data.base_ip,
    gateway: data.gateway,
    macAddress: data.mac_address,
    prefixLengthFrom: data.prefix_length_from,
    prefixLengthTo: data.prefix_length_to,
})