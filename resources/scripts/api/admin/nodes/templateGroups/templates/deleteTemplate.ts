import http from '@/api/http'

const deleteTemplate = (nodeId: number, groupUuid: string, templateUuid: string) =>
    http.delete(`/admin/nodes/${nodeId}/template_groups/${groupUuid}/templates/${templateUuid}`)

export default deleteTemplate