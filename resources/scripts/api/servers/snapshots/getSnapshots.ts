import axios from '@/lib/axios.ts'

import { rawDataToSnapshot } from '@/api/transformers/snapshot.ts'


const getSnapshots = async (uuid: string) => {
    const { data } = await axios.get(`/api/client/servers/${uuid}/snapshots`)

    return data ? rawDataToSnapshot(data) : null
}

export default getSnapshots
