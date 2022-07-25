import axios from 'axios'

export interface ServerTemplate {
    id: number
    server_id: number
    server: {
        id: number
        vmid: number
        name: string
    }
}

export default (serverId: number) => {
    return axios.get<ServerTemplate[]>(route('servers.get-templates', serverId))
}