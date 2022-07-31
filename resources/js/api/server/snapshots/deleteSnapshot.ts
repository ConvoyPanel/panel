import axios from 'axios'

export default (name: string, serverId: number) => {
    return axios.delete(route('servers.show.snapshots', { name, server: serverId }))
}