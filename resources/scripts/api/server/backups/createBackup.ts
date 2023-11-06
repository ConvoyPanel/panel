import http from '@/api/http'
import { Backup, rawDataToBackupObject } from '@/api/server/backups/getBackups'

export interface RequestParameters {
    name: string
    locked?: boolean
    mode: 'snapshot' | 'suspend' | 'kill'
    compressionType: 'none' | 'lzo' | 'gzip' | 'zstd'
}

export default async (
    uuid: string,
    params: RequestParameters
): Promise<Backup> => {
    const {
        data: { data },
    } = await http.post(`/api/client/servers/${uuid}/backups/`, {
        name: params.name,
        locked: params.locked,
        mode: params.mode,
        compression_type: params.compressionType,
    })

    return rawDataToBackupObject(data)
}