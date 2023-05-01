import http from '@/api/http'

export interface ConsoleCredentials {
    ticket: string
    node: string
    vmid: number
    fqdn: string
    port: number
}

export default (uuid: string): Promise<ConsoleCredentials> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/terminal`)
            .then(({ data: { data } }) => resolve(data))
            .catch(reject)
    })
}
