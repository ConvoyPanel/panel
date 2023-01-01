import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes'
import http from '@/api/http'

interface UpdateNodeParameters extends Omit<Node, 'id' | 'serversCount' | 'memoryAllocated' | 'diskAllocated'> {
    tokenId?: string
    secret?: string
}

const updateNode = async (nodeId: number, payload: UpdateNodeParameters) => {
    const {
        data: { data },
    } = await http.put(`/api/admin/nodes/${nodeId}`, {
        location_id: payload.locationId,
        name: payload.name,
        cluster: payload.cluster,
        fqdn: payload.fqdn,
        token_id: payload.tokenId,
        secret: payload.secret,
        port: payload.port,
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
