import axios from 'axios'

export default (template_id: number, serverId: number) => {
    return axios.post(route('servers.show.settings.rebuild', serverId), {
        template_id
    })
}