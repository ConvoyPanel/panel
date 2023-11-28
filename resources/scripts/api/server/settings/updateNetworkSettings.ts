import http from '@/api/http'

const updateNetworkSettings = (uuid: string, nameservers: string[]) => {
    return http.put(`/api/client/servers/${uuid}/settings/network`, {
        nameservers,
    })
}

export default updateNetworkSettings
