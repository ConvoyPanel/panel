import http from '@/api/http'

const deleteTemplate = (
    nodeId: number,
    groupUuid: string,
    templateUuid: string
) =>
    http.delete(
        `/api/admin/nodes/${nodeId}/template-groups/${groupUuid}/templates/${templateUuid}`
    )

export default deleteTemplate