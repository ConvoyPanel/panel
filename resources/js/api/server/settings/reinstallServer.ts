import axios from 'axios'

export default (template_id: number, serverId: number) => {
    return axios.post(route('servers.settings.reinstall', serverId), {
        template_id
    })
}