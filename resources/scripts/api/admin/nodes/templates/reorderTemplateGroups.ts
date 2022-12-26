import { rawDataToTemplateGroup, TemplateGroup } from '@/api/admin/nodes/templates/getTemplates'
import http from '@/api/http'


const reorderTemplateGroups = async (nodeId: number, groups: number[]): Promise<TemplateGroup[]> => {
    const { data: { data } } = await http.post(`/api/admin/nodes/${nodeId}/templates/reorder`, {
        order: groups,
    })

    return data.map(rawDataToTemplateGroup)
}

export default reorderTemplateGroups