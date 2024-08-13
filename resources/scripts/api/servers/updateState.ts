import axios from '@/lib/axios.ts'

export type PowerAction = 'start' | 'shutdown' | 'kill' | 'restart'

const updateState = async (uuid: string, state: PowerAction) => {
    await axios.patch(`/api/client/servers/${uuid}/state`, {
        state,
    })
}

export default updateState
