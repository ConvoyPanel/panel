import http, { FractalResponseData } from '@/api/http'
import { EloquentStatus } from '@/api/server/types'

export interface Address {
  id: number
  address: string
  cidr: number
  gateway: string
  macAddress?: string
}

export interface Server {
  id: string
  internalId: number
  uuid: string
  name: string
  description?: string
  status: EloquentStatus
  node_id: number
  usages: {
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
}

export const rawDataToAddressObject = (data: any): Address => ({
  id: data.id,
  address: data.address,
  cidr: data.cidr,
  gateway: data.gateway,
  macAddress: data.mac_address,
})

export const rawDataToServerObject = (data: FractalResponseData): Server => ({
  //@ts-ignore
  id: data.id,
  internalId: data.internal_id,
  uuid: data.uuid,
  name: data.name,
  status: data.status,
  node_id: data.node_id,
  description: data.description
    ? data.description.length > 0
      ? data.description
      : null
    : null,
  usages: {
    bandwidthUsage: data.usages.bandwidth_usage,
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
        rawDataToAddressObject(address)
      ),
      ipv6: data.limits.addresses.ipv6.map((address: any) =>
        rawDataToAddressObject(address)
      ),
    },
  },
})

export default (uuid: string): Promise<Server> => {
  return new Promise((resolve, reject) => {
    http
      .get(`/api/client/servers/${uuid}`)
      .then(({ data }) => resolve(rawDataToServerObject(data.data)))
      .catch(reject)
  })
}
