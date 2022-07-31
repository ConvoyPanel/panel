import axios from 'axios'

export default (mode: string, compression: string, serverId: number) => {
    return axios.post(route('servers.show.backups', serverId), {
        mode,
        compression,
    })
}