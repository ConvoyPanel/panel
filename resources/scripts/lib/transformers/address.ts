import {
    Address,
    AddressState,
    AddressStateReason,
    AddressVersion,
    GeneratedAddressesResult,
} from '@/types/address.ts'

import { rawDataToAddressBlock } from '@/lib/transformers/address-block.ts'

export const rawDataToAddress = (data: any): Address => ({
    id: data.id,
    addressBlockId: data.addressBlockId,
    serverId: data.serverId,
    state: data.state as AddressState,
    stateReason: (data.stateReason ?? null) as AddressStateReason | null,
    version:
        data.version === 'ipv4' ? AddressVersion.IPv4 : AddressVersion.IPv6,
    ip: data.ip,
    prefixLength: data.prefixLength,
    gateway: data.gateway,
    macAddress: data.macAddress,
    server: data.server ?? undefined,
    addressBlock: data.addressBlock
        ? rawDataToAddressBlock(data.addressBlock)
        : undefined,
})

export const rawDataToGeneratedAddressesResult = (
    data: any
): GeneratedAddressesResult => data as GeneratedAddressesResult
