import { ISO, rawDataToISO } from '@/api/admin/nodes/isos/getIsos'
import http from '@/api/http'

const updateIso = async (
    nodeId: number,
    isoUuid: string,
    name: string,
    hidden: boolean
): Promise<ISO> => {
    const {
        data: { data },
    } = await http.put(`/api/admin/nodes/${nodeId}/isos/${isoUuid}`, {
        name,
        hidden,
    })

    return rawDataToISO(data)
}

export default updateIso