import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes'
import http from '@/api/http'

interface CreateNodeParameters {
    locationId: number
    name: string
    cluster: string
    verifyTls: boolean
    fqdn: string
    tokenId: string
    secret: string
    port: number
    memory: number
    memoryOverallocate: number
    disk: number
    diskOverallocate: number
    vmStorage: string
    backupStorage: string
    isoStorage: string
    network: string
}

const createNode = async (data: CreateNodeParameters): Promise<Node> => {
    const {
        data: { data: responseData },
    } = await http.post('/api/admin/nodes', {
        location_id: data.locationId,
        name: data.name,
        cluster: data.cluster,
        verify_tls: data.verifyTls,
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
