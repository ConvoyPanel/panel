import http from '@/api/http'

const deleteServer = (serverUuid: string, noPurge?: boolean) => {
    return http.delete(`/api/admin/servers/${serverUuid}`, {
        data: {
            no_purge: noPurge,
        },
    })
}

export default deleteServer