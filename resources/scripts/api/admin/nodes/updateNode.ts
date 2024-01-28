import { rawDataToNode } from '@/api/admin/nodes/getNodes'
import http from '@/api/http'

interface UpdateNodeParameters {
    locationId: number
    name: string
    cluster: string
    verifyTls: boolean
    fqdn: string
    port: number
    tokenId?: string | null
    secret?: string | null
    memory: number
    memoryOverallocate: number
    disk: number
    diskOverallocate: number
    vmStorage: string
    backupStorage: string
    isoStorage: string
    network: string
}

const updateNode = async (nodeId: number, payload: UpdateNodeParameters) => {
    const {
        data: { data },
    } = await http.put(`/api/admin/nodes/${nodeId}`, {
        location_id: payload.locationId,
        name: payload.name,
        cluster: payload.cluster,
        verify_tls: payload.verifyTls,
        fqdn: payload.fqdn,
        port: payload.port,
        token_id: payload.tokenId ? payload.tokenId : undefined,
        secret: payload.secret ? payload.secret : undefined,
        memory: payload.memory,
        memory_overallocate: payload.memoryOverallocate,
        disk: payload.disk,
        disk_overallocate: payload.diskOverallocate,
        vm_storage: payload.vmStorage,
        backup_storage: payload.backupStorage,
        iso_storage: payload.isoStorage,
        network: payload.network,
    })

    return rawDataToNode(data)
}

export default updateNode
