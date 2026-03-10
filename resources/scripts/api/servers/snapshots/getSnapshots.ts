import axios from '@/lib/axios.ts'

import { rawDataToSnapshot } from '@/api/transformers/snapshot.ts'


const getSnapshots = async (uuid: string) => {
    const { data } = await axios.get(`/api/client/servers/${uuid}/snapshots`)

    return {
        snapshot: data.data && !Array.isArray(data.data) ? rawDataToSnapshot(data.data) : null,
        currentSnapshotUuid: data.meta?.current_snapshot_uuid || null,
    }
}

export default getSnapshots
