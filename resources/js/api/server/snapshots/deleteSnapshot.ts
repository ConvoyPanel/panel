import axios from 'axios'

export default (name: string, serverId: number) => {
    return axios.delete(route('servers.snapshots', { name, server: serverId }))
}