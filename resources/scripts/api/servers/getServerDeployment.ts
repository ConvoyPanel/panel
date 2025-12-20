import axios from '@/lib/axios.ts'

import { rawDataToDeployment } from '@/api/transformers/deployment.ts'


const getServerDeployment = async (uuid: string) => {
    const {
        data: { data },
    } = await axios.get(`/api/client/servers/${uuid}/deployment`)

    return rawDataToDeployment(data)
}

export default getServerDeployment
