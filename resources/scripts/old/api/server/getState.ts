import http from '@/api/http'

export type ServerState = 'running' | 'stopped'

export interface ServerStateData {
    state: ServerState
    cpuUsed: number
    memoryTotal: number
    memoryUsed: number
    uptime: number
}

export const rawDataToServerStateData = (data: any) => ({
    state: data.state,
    cpuUsed: data.cpu_used,
    memoryTotal: data.memory_total,
    memoryUsed: data.memory_used,
    uptime: data.uptime,
})

const getState = (uuid: string): Promise<ServerStateData> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/state`)
            .then(({ data: { data } }) =>
                resolve(rawDataToServerStateData(data))
            )
            .catch(reject)
    })
}

export default getState