import http from '@/api/http'

const deleteServer = (serverUuid: string) => {
    return http.delete(`/api/admin/servers/${serverUuid}`)
}

export default deleteServer