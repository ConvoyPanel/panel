import axios from '@/lib/axios.ts'

import { rawDataToServerStateData } from '@/api/transformers/server.ts'


const getState = async (uuid: string) => {
    const {
        data: { data },
    } = await axios.get(`/api/client/servers/${uuid}/state`)

    return rawDataToServerStateData(data)
}

export default getState
