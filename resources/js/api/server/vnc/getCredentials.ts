import axios from 'axios'

/*
// deprecated
export interface VncCredentials {
    upid: string
    port: string
    cert: string
    user: string
    ticket: string
    endpoint: string
} */

export interface VncCredentials {
    node_id: string
    vmid: number
    token: string
    endpoint: string
}

export default (serverId: number) => {
    return axios.get<VncCredentials>(route('servers.show.security.vnc.credentials', { id: serverId }))
}