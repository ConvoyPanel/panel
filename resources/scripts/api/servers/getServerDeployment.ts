import axios from '@/lib/axios.ts'

import { rawDataToDeployment } from '@/api/transformers/deployment'


const getServerDeployment = async (uuid: string) => {
    const { data, status } = await axios.get(`/api/client/servers/${uuid}/deployment`)

    if (status === 204) {
        return null
    }

    return rawDataToDeployment(data.data)
}

export default getServerDeployment
