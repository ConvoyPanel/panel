import { ServerContext } from '@/state/server'
import { ServerState } from '@/api/server/getStatus'
import useSWR from 'swr'
import http from '@/api/http'

export interface Disk {
    name: string
    size: number
    displayName: string | null
}

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
    name: data.name,
    size: data.size,
    displayName: data.display_name,
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
