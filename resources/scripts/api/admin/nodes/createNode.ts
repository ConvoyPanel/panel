import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes'
import http from '@/api/http'

interface CreateNodeParameters extends Omit<Node, 'id' | 'serversCount' | 'memoryAllocated' | 'diskAllocated'> {
    tokenId: string
    secret: string
}

const createNode = async (data: CreateNodeParameters): Promise<Node> => {
    const { data: { data: responseData } } = await http.post('/api/admin/nodes', {
        location_id: data.locationId,
        name: data.name,
        cluster: data.cluster,
        fqdn: data.fqdn,
        token_id: data.tokenId,
        secret: data.secret,
        port: data.port,
        memory: data.memory,
        memory_overallocate: data.memoryOverallocate,
        disk: data.disk,
        disk_overallocate: data.diskOverallocate,
        vm_storage: data.vmStorage,
        backup_storage: data.backupStorage,
        iso_storage: data.isoStorage,
        network: data.network,
    })

    return rawDataToNode(responseData)
}

export default createNode