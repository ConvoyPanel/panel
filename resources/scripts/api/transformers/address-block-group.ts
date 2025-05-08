import { AddressBlockGroup } from '@/types/address-block-group.ts'

export const rawDataToAddressBlockGroup = (raw: any): AddressBlockGroup => ({
    id: raw.id,
    name: raw.name,
    description: raw.description,
    addressBlocksCount: raw.address_blocks_count,
    nodesCount: raw.nodes_count,
})