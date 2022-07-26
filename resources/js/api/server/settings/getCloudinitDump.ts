import axios from 'axios'

// this interface is not complete
export interface Subnet {
    type: string
    address: string
    netmask?: string
    gateway?: string
}

export interface Config {
    type: string
    name?: string
    mac_address?: string
    subnets?: Subnet[]
    address?: string[]
    search?: string[]
}

export interface CloudinitDump {
    version: number
    config: Config[]
}

export default (serverId: number) => {
    return axios.get<CloudinitDump>(route('servers.show.settings.cloudinit.dump-config', serverId))
}