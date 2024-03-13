import http from '@/api/http'

export type PowerAction = 'start' | 'shutdown' | 'kill' | 'restart'

const updateState = (uuid: string, state: PowerAction) => {
    return http.patch(`/api/client/servers/${uuid}/state`, {
        state,
    })
}

export default updateState