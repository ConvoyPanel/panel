import axios from 'axios'

export default (template_vmid: number, serverId: number) => {
    return axios.post(route('servers.show.settings.reinstall', serverId), {
        template_vmid
    })
}