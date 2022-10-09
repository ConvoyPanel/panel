import axios from 'axios'

export type PowerAction = 'start' | 'shutdown' | 'kill' | 'reboot'

export default (action: PowerAction, id: number) => {
    return axios.post(route('servers.show.status', id), {
        action
    })
}