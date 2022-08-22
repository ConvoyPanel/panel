import { ServerTemplate, Template } from '@/api/admin/servers/templates/types'
import axios from 'axios'

export default (nodeId: number) => {
    return axios.get<ServerTemplate[]>(route('admin.nodes.show.templates', nodeId))
}