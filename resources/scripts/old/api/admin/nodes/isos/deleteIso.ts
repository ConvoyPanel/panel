import http from '@/api/http'

const deleteIso = (nodeId: number, isoUuid: string) =>
    http.delete(`/api/admin/nodes/${nodeId}/isos/${isoUuid}`)

export default deleteIso