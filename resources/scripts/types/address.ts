import { Server } from '@/types/server.ts'

export enum AddressVersion {
    IPv4 = 'ipv4',
    IPv6 = 'ipv6',
}

export interface Address {
    id: number
    addressBlockId: number
    serverId: number | null
    type: AddressVersion
    ip: string
    prefixLength: number
    gateway: string
    macAddress?: string
    server?: Server
}
