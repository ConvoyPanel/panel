import { Node } from '@/api/admin/nodes/getNodes'
import { User } from '@/api/admin/users/getUsers'
import http from '@/api/http'
import { EloquentStatus } from '@/api/server/types'

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

export interface Server {
    id: number
    uuid: string
    uuidShort: string
    userId: number
    nodeId: number
    vmid: number
    hostname: string
    name: string
    description: string | null
    status: EloquentStatus
}

export interface ServerBuild {
    id: string
    internalId: number
    uuid: string
    hostname: string
    name: string
    description: string | null
    status: EloquentStatus
    nodeId: number
    usages: {
        bandwidth: number // bytes
    }
    limits: {
        cpu: number
        memory: number // bytes
        disk: number // bytes
        snapshots: number | null
        backups: number | null
        bandwidth: number | null // bytes
        addresses: {
            ipv4: Address[]
            ipv6: Address[]
        }
        macAddress: string | null
    }
    user?: User
    node?: Node
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

export const rawDataToServer = (data: any): Server => ({
    id: data.id,
    uuid: data.uuid,
    uuidShort: data.uuid_short,
    nodeId: data.nodeId,
    userId: data.userId,
    vmid: data.vmid,
    hostname: data.hostname,
    name: data.name,
    description: data.description,
    status: data.status,
})

export const rawDataToServerBuild = (data: any): ServerBuild => ({
    id: data.id,
    internalId: data.internal_id,
    uuid: data.uuid,
    hostname: data.hostname,
    name: data.name,
    status: data.status,
    nodeId: data.node_id,
    description: data.description
        ? data.description.length > 0
            ? data.description
            : null
        : null,
    usages: {
        bandwidth: data.usages.bandwidth,
    },
    limits: {
        cpu: data.limits.cpu,
        memory: data.limits.memory,
        disk: data.limits.disk,
        snapshots: data.limits.snapshots,
        backups: data.limits.backups,
        bandwidth: data.limits.bandwidth,
        addresses: {
            ipv4: data.limits.addresses.ipv4.map((address: any) =>
                rawDataToAddress(address)
            ),
            ipv6: data.limits.addresses.ipv6.map((address: any) =>
                rawDataToAddress(address)
            ),
        },
        macAddress: data.limits.mac_address,
    },
    user: data?.user?.data,
    node: data?.node?.data,
})

export default (uuid: string): Promise<ServerBuild> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}`)
            .then(({ data }) => resolve(rawDataToServerBuild(data.data)))
            .catch(reject)
    })
}