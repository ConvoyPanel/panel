import http from '@/api/http';

export type ServerState = 'running' | 'stopped'

export interface ServerStatus {
    state: ServerState
    uptime: number
    cpu: number
    memory: number
}

export default (uuid: string): Promise<ServerStatus> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/status`)
            .then(({ data: { data } }) => resolve(data))
            .catch(reject)
    })
};