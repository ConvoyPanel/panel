import axios from 'axios'

export type PowerAction = 'start' | 'shutdown' | 'kill' | 'reboot'

export default (action: PowerAction, id: string) => {
    return axios.post(route('servers.show.status', id), {
        action
    })
}