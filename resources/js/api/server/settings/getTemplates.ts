import { ServerTemplate } from '@/api/admin/servers/templates/types'
import axios from 'axios'

export default (serverId: number) => {
    return axios.get<ServerTemplate[]>(route('servers.show.templates', serverId))
}