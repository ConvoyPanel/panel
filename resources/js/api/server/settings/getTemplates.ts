import { ServerTemplate } from '@/api/admin/servers/templates/types'
import axios from 'axios'

export default (serverUuid: string) => {
    return axios.get<ServerTemplate[]>(route('servers.show.templates', serverUuid))
}