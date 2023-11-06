import http from '@/api/http'

const deleteTemplateGroup = (nodeId: number, groupUuid: string) =>
    http.delete(`/api/admin/nodes/${nodeId}/template-groups/${groupUuid}`)

export default deleteTemplateGroup