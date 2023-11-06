import http from '@/api/http'

export default (uuid: string, nameservers: string[]) => {
    return http.put(`/api/client/servers/${uuid}/settings/network`, {
        nameservers,
    })
}