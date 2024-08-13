import { Server } from '@/types/server.ts'

import { rawDataToServer } from '@/api/transformers/server.ts'


export type AddressType = 'ipv4' | 'ipv6'

export interface Address {
    id: number
    addressPoolId: number
    serverId: number | null
    type: AddressType
    address: string
    cidr: number
    gateway: string
    macAddress?: string
    server?: Server
}

export const rawDataToAddress = (data: any): Address => ({
    id: data.id,
    addressPoolId: data.address_pool_id,
    serverId: data.server_id,
    type: data.type,
    address: data.address,
    cidr: data.cidr,
    gateway: data.gateway,
    macAddress: data.mac_address,
    server: data.server ? rawDataToServer(data.server.data) : undefined,
})
