import axios from '@/lib/axios'

import { rawDataToServerResources } from '@/api/transformers/server-resources'
import { ServerResources } from '@/types/server'

const getServerResources = async (uuid: string): Promise<ServerResources> => {
    const { data } = await axios.get(`/api/client/servers/${uuid}/resources`)
    return rawDataToServerResources(data.data)
}

export default getServerResources
