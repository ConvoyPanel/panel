import { Server } from '@/types/server.ts'

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
