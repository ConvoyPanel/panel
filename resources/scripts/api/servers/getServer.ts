import axios from '@/lib/axios.ts'

import { rawDataToServer } from '@/api/transformers/server.ts'


const getServer = async (uuid: string) => {
    const {
        data: { data },
    } = await axios.get(`/api/client/servers/${uuid}`)

    return rawDataToServer(data)
}

export default getServer
