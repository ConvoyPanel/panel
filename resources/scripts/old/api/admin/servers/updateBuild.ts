import { rawDataToAdminServer } from '@/api/admin/servers/getServer'
import http from '@/api/http'

interface UpdateServerBuildParameters {
    cpu: number
    memory: number
    disk: number
    addressIds: number[]
    snapshotLimit: number | null
    backupLimit: number | null
    bandwidthLimit: number | null
    bandwidthUsage: number
}

const updateBuild = async (
    serverUuid: string,
    {
        addressIds,
        snapshotLimit,
        backupLimit,
        bandwidthLimit,
        bandwidthUsage,
        ...params
    }: UpdateServerBuildParameters
) => {
    const {
        data: { data },
    } = await http.patch(`/api/admin/servers/${serverUuid}/settings/build`, {
        address_ids: addressIds,
        snapshot_limit: snapshotLimit,
        backup_limit: backupLimit,
        bandwidth_limit: bandwidthLimit,
        bandwidth_usage: bandwidthUsage,
        ...params,
    })

    return rawDataToAdminServer(data)
}

export default updateBuild