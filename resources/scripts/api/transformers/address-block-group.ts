import { AddressBlockGroup } from '@/types/address-block-group.ts'

export const rawDataToAddressBlockGroup = (raw: any): AddressBlockGroup => ({
    id: raw.id,
    name: raw.name,
    description: raw.description,
    nodesCount: raw.nodes_count,
})