import {
    TemplateGroup,
    rawDataToTemplateGroup,
} from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import http from '@/api/http'

export interface TemplateGroupParameters {
    name: string
    hidden: boolean
}

const createTemplateGroup = async (
    nodeId: number,
    parameters: TemplateGroupParameters
): Promise<TemplateGroup> => {
    const {
        data: { data },
    } = await http.post(
        `/api/admin/nodes/${nodeId}/template-groups`,
        parameters
    )

    return rawDataToTemplateGroup(data)
}

export default createTemplateGroup