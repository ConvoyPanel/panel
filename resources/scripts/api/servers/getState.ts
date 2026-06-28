import axios from '@/lib/axios.ts'
import type { ServerStateData } from '@/types/server.ts'

const getState = async (uuid: string): Promise<ServerStateData> => {
    const { data: { data } } = await axios.get(`/api/client/servers/${uuid}/state`)
    return {
        state: data.state,
        cpuUsed: data.cpuUsed,
        memoryTotal: data.memoryTotal,
        memoryUsed: data.memoryUsed,
        uptime: data.uptime,
    }
}

export default getState
