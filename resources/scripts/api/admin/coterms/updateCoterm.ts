import { rawDataToCoterm } from '@/api/admin/coterms/getCoterms'
import http from '@/api/http'

interface UpdateCotermParameters {
    name: string
    isTlsEnabled: boolean
    fqdn: string
    port: number
    nodeIds?: number[] | null
}

const updateCoterm = async (
    id: number,
    { name, isTlsEnabled, fqdn, port, nodeIds }: UpdateCotermParameters
) => {
    const {
        data: { data },
    } = await http.put(`/api/admin/coterms/${id}`, {
        name,
        is_tls_enabled: isTlsEnabled,
        fqdn,
        port,
        node_ids: nodeIds,
    })

    return rawDataToCoterm(data)
}

export default updateCoterm