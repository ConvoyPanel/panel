import {
    TemplateGroup,
    rawDataToTemplateGroup,
} from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import http from '@/api/http'

const reorderTemplateGroups = async (
    nodeId: number,
    groups: number[]
): Promise<TemplateGroup[]> => {
    const {
        data: { data },
    } = await http.post(`/api/admin/nodes/${nodeId}/template-groups/reorder`, {
        order: groups,
    })

    return data.map(rawDataToTemplateGroup)
}

export default reorderTemplateGroups