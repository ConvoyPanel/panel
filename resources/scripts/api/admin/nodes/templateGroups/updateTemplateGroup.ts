import { TemplateGroupParameters } from '@/api/admin/nodes/templateGroups/createTemplateGroup'
import {
    TemplateGroup,
    rawDataToTemplateGroup,
} from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import http from '@/api/http'

const updateTemplateGroup = async (
    nodeId: number,
    groupUuid: string,
    parameters: TemplateGroupParameters
): Promise<TemplateGroup> => {
    const {
        data: { data },
    } = await http.put(
        `/api/admin/nodes/${nodeId}/template-groups/${groupUuid}`,
        parameters
    )

    return rawDataToTemplateGroup(data)
}

export default updateTemplateGroup