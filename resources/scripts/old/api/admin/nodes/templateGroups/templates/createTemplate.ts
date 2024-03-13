import {
    Template,
    rawDataToTemplate,
} from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import http from '@/api/http'

export interface TemplateParameters {
    name: string
    vmid: number
    hidden: boolean
}

const createTemplate = async (
    nodeId: number,
    groupUuid: string,
    parameters: TemplateParameters
): Promise<Template> => {
    const {
        data: { data },
    } = await http.post(
        `/api/admin/nodes/${nodeId}/template-groups/${groupUuid}/templates`,
        parameters
    )

    return rawDataToTemplate(data)
}

export default createTemplate