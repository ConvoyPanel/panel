import { ServerTemplate } from '@/api/admin/servers/templates/types'
import axios from 'axios'

export default (id: number) => {
    return axios.get<ServerTemplate[]>(route('servers.show.templates', id))
}