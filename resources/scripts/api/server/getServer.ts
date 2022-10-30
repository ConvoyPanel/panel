import { FractalResponseData } from '@/api/http'
import { EloquentStatus } from '@/api/server/types'

export interface Address {
    address: string
    cidr: number
    gateway: string
    macAddress?: string
}

export interface Server {
    identifier: string
    internalId: number
    uuid: string
    name: string
    description?: string
    status: EloquentStatus
    usage: {
        bandwidthUsage: number // bytes
    }
    limits: {
        cpu: number
        memory: number // bytes
        disk: number // bytes
        snapshots?: number
        backups?: number
        bandwidth?: number // bytes
        addresses: {
            ipv4: Address[]
            ipv6: Address[]
        }
    }
    config: {
        template: boolean
    }
}

export const rawDataToServerObject = (data: FractalResponseData): Server => ({

    //@ts-ignore
    identifier: data.id,
    internalId: data.internal_id,
    uuid: data.uuid,
    name: data.name,
    status: data.status,
    description: data.description ? (data.description.length > 0 ? data.description : null) : null,
    usage: {
        bandwidthUsage: data.usage.bandwidth_usage,
    },
    limits: { ...data.limits },
    config: { ...data.config },
});