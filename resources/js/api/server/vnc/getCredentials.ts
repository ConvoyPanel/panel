import axios from 'axios'

export interface VncCredentials {
    node_id: string
    vmid: number
    token: string
    endpoint: string
}

export default (serverUuid: string) => {
    return axios.get<VncCredentials>(route('servers.show.security.vnc.credentials', { id: serverUuid }))
}