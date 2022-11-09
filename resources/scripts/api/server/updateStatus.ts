import http from '@/api/http'

export type PowerAction = 'start' | 'shutdown' | 'kill' | 'restart'

export default (uuid: string, state: PowerAction) => {
    return http.post(`/api/client/servers/${uuid}/status`, {
        state
    })
}