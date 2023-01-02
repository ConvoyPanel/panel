import http from '@/api/http'
import { rawDataToTemplate } from '@/api/admin/nodes/templateGroups/getTemplateGroups'

const getTemplates = async (nodeId: number, groupUuid: string) => {
    const {
        data: { data },
    } = await http.get(`/api/admin/nodes/${nodeId}/template-groups/${groupUuid}`)

    return data.map(rawDataToTemplate)
}

export default getTemplates
