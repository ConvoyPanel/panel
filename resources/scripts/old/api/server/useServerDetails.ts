import { ServerContext } from '@/state/server'
import useSWR from 'swr'

import http from '@/api/http'
import { ServerState } from '@/api/server/getState'


export interface BaseDisk {
    interface: string
    isPrimaryDisk: boolean
    size: number
}

export interface DiskWithMedia extends BaseDisk {
    isMedia: true
    mediaName: string
}

export interface DiskWithoutMedia extends BaseDisk {
    isMedia: false
    mediaName?: null
}

export type Disk = DiskWithMedia | DiskWithoutMedia

export interface Address {
    address: string
    cidr: number
    gateway: string
}

export interface ServerDetails {
    id: string
    internalId: number
    uuid: string
    nodeId: number
    state: ServerState
    locked: boolean
    config: {
        macAddress: string
        bootOrder: string
        disks: Disk[]
        addresses: {
            ipv4?: Address
            ipv6?: Address
        }
    }
}

export const rawDataToDisk = (data: any): Disk => ({
    interface: data.interface,
    isPrimaryDisk: data.is_primary_disk,
    isMedia: data.is_media,
    mediaName: data?.media_name,
    size: data.size,
})

export const rawDataToServerDetails = (data: any): ServerDetails => ({
    id: data.id,
    internalId: data.internal_id,
    uuid: data.uuid,
    nodeId: data.node_id,
    state: data.state,
    locked: data.locked,
    config: {
        macAddress: data.config.mac_address,
        bootOrder: data.config.boot_order,
        disks: data.config.disks.map(rawDataToDisk),
        addresses: data.config.addresses,
    },
})

const useProxmoxDetails = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)

    return useSWR<ServerDetails>(['server:details', uuid], async () => {
        const {
            data: { data },
        } = await http.get(`/api/client/servers/${uuid}/details`)

        return rawDataToServerDetails(data)
    })
}

export default useProxmoxDetails