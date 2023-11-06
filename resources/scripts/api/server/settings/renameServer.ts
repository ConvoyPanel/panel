import http from '@/api/http'

interface Payload {
    name: string
    hostname: string
}

export default (uuid: string, payload: Payload) => {
    return http.post(`/api/client/servers/${uuid}/settings/rename`, {
        ...payload,
    })
}