import axios from '@/lib/axios.ts'

import { rawDataToServer } from '@/api/transformers/server.ts'

const getServer = async (id: number) => {
    const { data: { data } } = await axios.get(`/api/admin/servers/${id}`)

    return rawDataToServer(data)
}

export default getServer