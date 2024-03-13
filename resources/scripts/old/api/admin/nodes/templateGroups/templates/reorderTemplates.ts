import {
    Template,
    rawDataToTemplate,
} from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import http from '@/api/http'

const reorderTemplates = async (
    nodeId: number,
    groupUuid: string,
    templates: number[]
): Promise<Template[]> => {
    const {
        data: { data },
    } = await http.post(
        `/api/admin/nodes/${nodeId}/template-groups/${groupUuid}/templates/reorder`,
        {
            order: templates,
        }
    )

    return data.map(rawDataToTemplate)
}

export default reorderTemplates