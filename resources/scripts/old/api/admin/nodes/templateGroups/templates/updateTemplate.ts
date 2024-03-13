import {
    Template,
    rawDataToTemplate,
} from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import { TemplateParameters } from '@/api/admin/nodes/templateGroups/templates/createTemplate'
import http from '@/api/http'

const updateTemplate = async (
    nodeId: number,
    groupUuid: string,
    templateUuid: string,
    parameters: TemplateParameters
): Promise<Template> => {
    const {
        data: { data },
    } = await http.put(
        `/api/admin/nodes/${nodeId}/template-groups/${groupUuid}/templates/${templateUuid}`,
        parameters
    )

    return rawDataToTemplate(data)
}

export default updateTemplate