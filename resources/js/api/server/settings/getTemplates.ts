import axios from 'axios'

export interface ServerTemplate {
    id: number
    name: string
    vmid: number
}

export default () => {
    return axios.get<ServerTemplate[]>(route('servers.get-templates'))
}