import http from '@/api/http'

interface Response {
    token: string
    node: string
    vmid: number
    hostname: string
    port: number
}

export default (uuid: string): Promise<Response> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/terminal`)
            .then(({ data: { data } }) => resolve(data))
            .catch(reject)
    })
}