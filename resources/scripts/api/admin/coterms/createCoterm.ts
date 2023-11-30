import { rawDataToCoterm } from '@/api/admin/coterms/getCoterms'
import http from '@/api/http'

interface CreateCotermParameters {
    name: string
    isTlsEnabled: boolean
    fqdn: string
    port: number
    nodeIds?: number[] | null
}

const createCoterm = async ({
    name,
    isTlsEnabled,
    fqdn,
    port,
    nodeIds,
}: CreateCotermParameters) => {
    const {
        data: { data },
    } = await http.post('/api/admin/coterms', {
        name,
        is_tls_enabled: isTlsEnabled,
        fqdn,
        port,
        node_ids: nodeIds,
    })

    return rawDataToCoterm(data)
}

export default createCoterm